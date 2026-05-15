<?php

namespace App\Services;

use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Webhook;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  CUSTOMERS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Create a new Stripe Customer.
     *
     * @param  array{email: string, name?: string, phone?: string, address?: array, metadata?: array}  $data
     */
    public function createCustomer(array $data): Customer
    {
        return Customer::create(array_filter([
            'email'    => $data['email'],
            'name'     => $data['name']    ?? null,
            'phone'    => $data['phone']   ?? null,
            'address'  => $data['address'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]));
    }

    /**
     * Retrieve an existing Stripe Customer by ID.
     */
    public function getCustomer(string $customerId): Customer
    {
        return Customer::retrieve($customerId);
    }

    /**
     * Find a customer by email. Returns the first match or null.
     */
    public function findCustomerByEmail(string $email): ?Customer
    {
        $results = Customer::search(['query' => "email:'{$email}'"]);

        return $results->data[0] ?? null;
    }

    /**
     * Update mutable fields on a Stripe Customer.
     */
    public function updateCustomer(string $customerId, array $data): Customer
    {
        return Customer::update($customerId, $data);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PAYMENT METHODS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Attach a payment method to a customer and optionally set it as the
     * default for future invoices and payments.
     */
    public function addPaymentMethodToCustomer(
        string $paymentMethodId,
        string $customerId,
        bool   $setAsDefault = true
    ): PaymentMethod {
        $pm = PaymentMethod::retrieve($paymentMethodId);
        $pm->attach(['customer' => $customerId]);

        if ($setAsDefault) {
            Customer::update($customerId, [
                'invoice_settings' => ['default_payment_method' => $paymentMethodId],
            ]);
        }

        return $pm;
    }

    /**
     * Retrieve details of a payment method.
     */
    public function getPaymentMethod(string $paymentMethodId): PaymentMethod
    {
        return PaymentMethod::retrieve($paymentMethodId);
    }

    /**
     * List all payment methods of a given type attached to a customer.
     *
     * @return PaymentMethod[]
     */
    public function listPaymentMethods(string $customerId, string $type = 'card'): array
    {
        return PaymentMethod::all([
            'customer' => $customerId,
            'type'     => $type,
        ])->data;
    }

    /**
     * Detach a payment method from its customer.
     */
    public function removePaymentMethod(string $paymentMethodId): PaymentMethod
    {
        $pm = PaymentMethod::retrieve($paymentMethodId);
        $pm->detach();

        return $pm;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PAYMENT INTENTS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Create a PaymentIntent without confirming it.
     * Use this when you need to return a client_secret to the frontend for
     * Stripe.js confirmation.
     *
     * @param  array{
     *     amount_cents: int,
     *     currency?: string,
     *     customer_id?: string,
     *     payment_method_id?: string,
     *     description?: string,
     *     metadata?: array,
     *     receipt_email?: string,
     * }  $data
     */
    public function createPaymentIntent(array $data): PaymentIntent
    {
        return PaymentIntent::create(array_filter([
            'amount'               => $data['amount_cents'],
            'currency'             => $data['currency']          ?? 'usd',
            'customer'             => $data['customer_id']        ?? null,
            'payment_method'       => $data['payment_method_id'] ?? null,
            'description'          => $data['description']        ?? null,
            'receipt_email'        => $data['receipt_email']      ?? null,
            'metadata'             => $data['metadata']           ?? [],
            'automatic_payment_methods' => ['enabled' => true,'allow_redirects' => 'never'],
        ]));
    }

    /**
     * Confirm and immediately charge a PaymentIntent using the given
     * payment method. Returns the confirmed PaymentIntent.
     *
     * Status will be 'succeeded' on success, or 'requires_action' if the
     * card needs 3DS authentication.
     */
    public function processPayment(
        string $paymentIntentId,
        string $paymentMethodId,
        bool   $returnUrl = false,
        string $returnUrlValue = ''
    ): PaymentIntent {
        $params = ['payment_method' => $paymentMethodId];

        if ($returnUrl && $returnUrlValue) {
            $params['return_url'] = $returnUrlValue;
        }

        return PaymentIntent::retrieve($paymentIntentId)->confirm($params);
    }

    /**
     * Create and immediately confirm a PaymentIntent in one call.
     * Convenience wrapper for server-side only flows where 3DS is not
     * expected (e.g. saved payment methods with off-session flag).
     *
     * @param  array{
     *     amount_cents: int,
     *     payment_method_id: string,
     *     customer_id?: string,
     *     description?: string,
     *     receipt_email?: string,
     *     metadata?: array,
     *     off_session?: bool,
     * }  $data
     */
    public function createAndConfirmPayment(array $data): PaymentIntent
    {
        return PaymentIntent::create(array_filter([
            'amount'               => $data['amount_cents'],
            'currency'             => 'usd',
            'customer'             => $data['customer_id']        ?? null,
            'payment_method'       => $data['payment_method_id'],
            'description'          => $data['description']        ?? null,
            'receipt_email'        => $data['receipt_email']      ?? null,
            'metadata'             => $data['metadata']           ?? [],
            'confirm'              => true,
            'off_session'          => $data['off_session']        ?? false,
            'return_url'           => config('app.url') . '/membership/payment-return',
        ]));
    }

    /**
     * Retrieve a PaymentIntent by ID.
     */
    public function getPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  REFUNDS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Refund a charge fully or partially.
     *
     * Pass either a payment_intent_id or a charge_id; amount_cents is
     * optional (omit for full refund).
     *
     * @param  array{
     *     payment_intent_id?: string,
     *     charge_id?: string,
     *     amount_cents?: int,
     *     reason?: string,
     *     metadata?: array,
     * }  $data
     */
    public function refundPayment(array $data): Refund
    {
        $params = array_filter([
            'payment_intent' => $data['payment_intent_id'] ?? null,
            'charge'         => $data['charge_id']          ?? null,
            'amount'         => $data['amount_cents']        ?? null,
            'reason'         => $data['reason']              ?? null,
            'metadata'       => $data['metadata']            ?? [],
        ]);

        return Refund::create($params);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SUBSCRIPTIONS  (Checkomatic recurring plans)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Create a Stripe Subscription for a customer.
     *
     * @param  array{
     *     customer_id: string,
     *     price_id: string,
     *     payment_method_id?: string,
     *     trial_days?: int,
     *     metadata?: array,
     * }  $data
     */
    public function createSubscription(array $data): Subscription
    {
        $params = array_filter([
            'customer'               => $data['customer_id'],
            'items'                  => [['price' => $data['price_id']]],
            'default_payment_method' => $data['payment_method_id'] ?? null,
            'trial_period_days'      => $data['trial_days']         ?? null,
            'metadata'               => $data['metadata']           ?? [],
            'payment_behavior'       => 'default_incomplete',
            'expand'                 => ['latest_invoice.payment_intent'],
        ]);

        return Subscription::create($params);
    }

    /**
     * Retrieve a Subscription by ID.
     */
    public function getSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId);
    }

    /**
     * Cancel a subscription immediately or at period end.
     */
    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = false): Subscription
    {
        if ($atPeriodEnd) {
            return Subscription::update($subscriptionId, ['cancel_at_period_end' => true]);
        }

        $sub = Subscription::retrieve($subscriptionId);
        $sub->cancel();

        return $sub;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  CHECKOUT SESSIONS  (hosted payment page)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Create a hosted Stripe Checkout Session.
     *
     * @param  array{
     *     amount_cents: int,
     *     product_name: string,
     *     customer_email?: string,
     *     customer_id?: string,
     *     payment_method_id?: string,
     *     reference_id?: string,
     *     success_url: string,
     *     cancel_url: string,
     *     mode?: string,
     *     metadata?: array,
     * }  $data
     */
    public function createCheckoutSession(array $data): CheckoutSession
    {
        $mode = $data['mode'] ?? 'payment';

        $params = array_filter([
            'mode'                => $mode,
            'customer'            => $data['customer_id']        ?? null,
            'customer_email'      => isset($data['customer_id']) ? null : ($data['customer_email'] ?? null),
            'client_reference_id' => $data['reference_id']       ?? null,
            'success_url'         => $data['success_url'],
            'cancel_url'          => $data['cancel_url'],
            'metadata'            => $data['metadata']            ?? [],
        ]);

        if ($mode === 'payment') {
            $params['line_items'] = [[
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $data['amount_cents'],
                    'product_data' => ['name' => $data['product_name']],
                ],
                'quantity' => 1,
            ]];

            if (! empty($data['payment_method_id'])) {
                $params['payment_method_options'] = [];
                $params['payment_intent_data']    = [
                    'payment_method' => $data['payment_method_id'],
                ];
            }
        }

        return CheckoutSession::create($params);
    }

    /**
     * Retrieve a Checkout Session by ID.
     */
    public function retrieveCheckoutSession(string $sessionId): CheckoutSession
    {
        return CheckoutSession::retrieve($sessionId);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  WEBHOOKS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Verify and parse an incoming Stripe webhook request.
     *
     * @throws SignatureVerificationException  if the signature is invalid
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        return Webhook::constructEvent(
            $payload,
            $sigHeader,
            config('services.stripe.webhook_secret')
        );
    }
}
