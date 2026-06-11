{{--
  Renewal modal — shared by the member dashboard and profile pages.
  Self-contained: carries its own CSS, HTML, and JS. The renewal JS IIFE
  early-returns when #renewModal is absent (lifetime members), so including
  this partial is always safe.
  Requires: $profile (App\Support\MemberProfile) in scope.
--}}
@php
  $isLifetime = stripos(($profile->level ?? ''), 'lifetime') !== false;
  $isExpired  = $profile->isExpired();
@endphp
<style>
    /* ── Renewal modal ── */
    .renew-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(15, 23, 42, 0.5);
      z-index: 80;
      align-items: center; justify-content: center;
      padding: 20px;
    }
    .renew-overlay.open { display: flex; }
    .renew-modal {
      background: var(--surface);
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      width: 100%;
      max-width: 440px;
      padding: 26px 26px 24px;
      position: relative;
      max-height: 92vh;
      overflow-y: auto;
    }
    .renew-close {
      position: absolute; top: 14px; right: 14px;
      width: 32px; height: 32px;
      border: none; background: var(--bg);
      border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
      color: var(--text-muted);
      transition: background .15s, color .15s;
    }
    .renew-close:hover { background: #e9edf0; color: var(--text); }
    .renew-close svg { width: 16px; height: 16px; stroke-width: 2; }
    .renew-screen { display: none; }
    .renew-screen.active { display: block; }
    .renew-icon-circle {
      width: 56px; height: 56px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 14px;
    }
    .renew-icon-circle.warn { background: #fee2e2; color: #b91c1c; }
    .renew-icon-circle.ok   { background: var(--green-soft); color: var(--green); }
    .renew-icon-circle svg { width: 26px; height: 26px; stroke-width: 2; }
    .renew-modal h3 { font-size: 19px; font-weight: 700; letter-spacing: -0.3px; color: var(--text); }
    .renew-modal p.renew-sub { font-size: 13px; color: var(--text-muted); margin-top: 6px; line-height: 1.55; }
    .renew-pay-row {
      display: flex; align-items: center; justify-content: space-between;
      background: linear-gradient(135deg, #0d7a55 0%, #064e36 100%);
      border-radius: var(--radius-sm);
      padding: 16px 18px;
      margin: 16px 0 14px;
      box-shadow: 0 6px 18px rgba(6,78,54,0.25);
    }
    .renew-pay-row .pay-info { display: flex; flex-direction: column; gap: 3px; }
    .renew-pay-row .label { font-size: 15px; font-weight: 700; color: #ffffff; }
    .renew-pay-row .pay-sub { font-size: 12px; font-weight: 400; color: rgba(255,255,255,0.8); }
    .renew-pay-row .amount { font-size: 20px; font-weight: 800; color: #ffffff; }
    .renew-field { margin-bottom: 14px; }
    .renew-field label {
      display: block; font-size: 12px; font-weight: 500;
      color: #475569; margin-bottom: 6px;
    }
    .renew-field input[type="number"],
    .renew-field input[type="text"] {
      width: 100%;
      background: #f4f6f8;
      border: 1px solid transparent;
      border-radius: 10px;
      padding: 11px 14px;
      font-size: 14px; color: var(--text);
      font-family: inherit; outline: none;
      transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .renew-field input[type="number"]:focus,
    .renew-field input[type="text"]:focus {
      background: #fff; border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(13,122,82,0.12);
    }
    #renew-card-element {
      background: #f4f6f8;
      border: 1px solid #e6ebe8;
      border-radius: 10px;
      padding: 13px 14px;
    }
    .renew-card-label {
      display: block; font-size: 12px; font-weight: 500;
      color: #475569; margin-bottom: 6px;
    }
    .renew-error {
      display: none;
      font-size: 12px; color: var(--danger);
      margin-top: 8px;
    }
    .renew-error.show { display: block; }
    .renew-actions {
      display: flex; gap: 10px; margin-top: 18px;
    }
    .renew-btn {
      flex: 1;
      border-radius: 999px;
      padding: 11px 16px;
      font-size: 14px; font-weight: 600;
      border: none;
      transition: background .15s, color .15s, box-shadow .15s, opacity .15s;
    }
    .renew-btn-primary {
      background: linear-gradient(135deg, #0e7a52 0%, #0a5a3d 100%);
      color: #fff;
      box-shadow: 0 6px 16px rgba(13, 122, 82, 0.25);
    }
    .renew-btn-primary:hover { box-shadow: 0 8px 20px rgba(13, 122, 82, 0.32); }
    .renew-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; box-shadow: none; }
    .renew-btn-ghost {
      background: var(--bg);
      color: var(--text-muted);
      border: 1px solid var(--border);
    }
    .renew-btn-ghost:hover { background: #e9edf0; color: var(--text); }
    .renew-invoice {
      font-size: 13px; font-weight: 600; color: var(--green);
      background: var(--green-soft);
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      margin: 14px 0 4px;
      text-align: center;
    }
</style>
{{-- ── Renewal modal ── --}}
@unless($isLifetime)
<div class="renew-overlay" id="renewModal" aria-hidden="true">
  <div class="renew-modal" role="dialog" aria-modal="true">
    <button type="button" class="renew-close" id="renewCloseBtn" aria-label="Close">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>

    {{-- SCREEN 1 — confirm --}}
    <div class="renew-screen active" id="renewScreenConfirm">
      <div class="renew-icon-circle warn">
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
      </div>
      <h3 id="renewConfirmTitle">{{ $isExpired ? 'Membership Expired!' : 'Renew your membership' }}</h3>
      <p class="renew-sub">Renewing extends your membership and keeps your access uninterrupted. You can review the fee on the next step before paying.</p>
      <div class="renew-error" id="renewConfirmError"></div>
      <div class="renew-actions">
        <button type="button" class="renew-btn renew-btn-ghost" id="renewNotNowBtn">Not Now</button>
        <button type="button" class="renew-btn renew-btn-primary" id="renewConfirmBtn">Yes, Renew Now</button>
      </div>
    </div>

    {{-- SCREEN 2 — payment --}}
    <div class="renew-screen" id="renewScreenPay">
      <h3>Complete Payment</h3>
      <p class="renew-sub">Enter your card details to complete your membership renewal securely.</p>

      <div class="renew-pay-row">
        <div class="pay-info">
          <span class="label">Pending Payment</span>
          <span class="pay-sub">Renew Membership</span>
        </div>
        <span class="amount" id="renewAmountLabel">—</span>
      </div>

      <div class="renew-field" id="renewMonthlyWrap" style="display:none;">
        <label for="renewMonthlyInput">Monthly contribution amount</label>
        <input id="renewMonthlyInput" type="number" min="1" step="0.01" placeholder="Monthly amount" />
      </div>

      <div class="renew-field">
        <label for="renewCardholderName">Cardholder Name</label>
        <input id="renewCardholderName" type="text" autocomplete="cc-name" placeholder="Name on card" />
      </div>

      <label class="renew-card-label" for="renew-card-element">Card details</label>
      <div id="renew-card-element"></div>

      <div class="renew-error" id="renewPayError"></div>

      <div class="renew-actions">
        <button type="button" class="renew-btn renew-btn-ghost" id="renewBackBtn">Back</button>
        <button type="button" class="renew-btn renew-btn-primary" id="renewPayBtn">Pay</button>
      </div>
    </div>

    {{-- SCREEN 3 — success --}}
    <div class="renew-screen" id="renewScreenSuccess">
      <div class="renew-icon-circle ok">
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>
      <h3>Renewal Successful!</h3>
      <p class="renew-sub">Your membership has been renewed. Thank you for staying with ISGH.</p>
      <div class="renew-invoice" id="renewInvoiceLabel">Processing your invoice…</div>
      <div class="renew-actions">
        <button type="button" class="renew-btn renew-btn-primary" id="renewDoneBtn">Go to Dashboard</button>
      </div>
    </div>
  </div>
</div>

<script>
  // ── Renewal modal module (self-contained IIFE) ──────────────────────────
  (function () {
    const modal = document.getElementById('renewModal');
    if (!modal) return; // lifetime members have no modal

    const renewCsrf      = document.querySelector('meta[name="csrf-token"]').content;
    const renewStripeKey = '{{ $stripePublishableKey ?? config("services.stripe.key") }}';

    const summaryUrl   = '{{ route('member-portal.renew.summary') }}';
    const renewUrl     = '{{ route('member-portal.renew') }}';
    const finalizeUrl  = '{{ route('member-portal.renew.finalize') }}';
    const statusUrlBase = '{{ url('/member-portal/renew/status') }}';
    const dashboardUrl = '{{ route('member-portal.dashboard') }}';

    const screens = {
      confirm: document.getElementById('renewScreenConfirm'),
      pay:     document.getElementById('renewScreenPay'),
      success: document.getElementById('renewScreenSuccess'),
    };
    const confirmBtn  = document.getElementById('renewConfirmBtn');
    const notNowBtn   = document.getElementById('renewNotNowBtn');
    const closeBtn    = document.getElementById('renewCloseBtn');
    const backBtn     = document.getElementById('renewBackBtn');
    const payBtn      = document.getElementById('renewPayBtn');
    const doneBtn     = document.getElementById('renewDoneBtn');
    const amountLabel = document.getElementById('renewAmountLabel');
    const monthlyWrap = document.getElementById('renewMonthlyWrap');
    const monthlyInput = document.getElementById('renewMonthlyInput');
    const cardholderInput = document.getElementById('renewCardholderName');
    const payError    = document.getElementById('renewPayError');
    const confirmError = document.getElementById('renewConfirmError');
    const invoiceLabel = document.getElementById('renewInvoiceLabel');

    let _stripe = null;
    let _cardElement = null;
    let _cardMounted = false;
    let _isCheckomatic = false;

    // ── Stripe init — mirrors the signup page (membership-types.blade.php) ──
    function initRenewCardElement() {
      if (_cardMounted) return;
      if (!renewStripeKey) { console.warn('[Renew][Stripe] publishable key not set'); return; }
      _stripe = Stripe(renewStripeKey);
      const elements = _stripe.elements();
      _cardElement = elements.create('card', {
        style: {
          base: {
            fontFamily: "'Inter', sans-serif",
            fontSize: '14px',
            color: '#111827',
            '::placeholder': { color: '#9ca3af' },
          },
          invalid: { color: '#dc2626' },
        },
        hidePostalCode: true,
      });
      _cardElement.mount('#renew-card-element');
      _cardElement.on('change', e => {
        if (e.error) showError(payError, e.error.message);
        else hideError(payError);
      });
      _cardMounted = true;
    }

    function showError(el, msg) { if (el) { el.textContent = msg; el.classList.add('show'); } }
    function hideError(el)      { if (el) { el.textContent = ''; el.classList.remove('show'); } }

    function showScreen(name) {
      Object.entries(screens).forEach(([k, el]) => {
        if (el) el.classList.toggle('active', k === name);
      });
    }

    function openModal() {
      hideError(confirmError);
      hideError(payError);
      if (monthlyInput) monthlyInput.value = '';
      if (invoiceLabel) invoiceLabel.textContent = '';
      showScreen('confirm');
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    // ── Open triggers: .btn-renew, .btn-renew-mobile, Quick Links renew link ──
    document.querySelectorAll('.btn-renew, .btn-renew-mobile, .ql-renew-link').forEach(el => {
      el.addEventListener('click', e => {
        e.preventDefault();
        openModal();
      });
    });

    notNowBtn?.addEventListener('click', closeModal);
    closeBtn?.addEventListener('click', closeModal);
    backBtn?.addEventListener('click', () => { hideError(payError); showScreen('confirm'); });
    doneBtn?.addEventListener('click', () => { location.href = dashboardUrl; });

    // ── SCREEN 1 → SCREEN 2: fetch renewal summary ──────────────────────────
    confirmBtn?.addEventListener('click', async () => {
      if (confirmBtn.disabled) return;
      hideError(confirmError);
      confirmBtn.disabled = true;
      try {
        const res = await fetch(summaryUrl, {
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': renewCsrf },
        });
        const data = await res.json();

        if (!data || data.renewable === false) {
          showError(confirmError, (data && data.message) || 'Your membership is not eligible for renewal.');
          return;
        }

        _isCheckomatic = !!data.isCheckomatic;
        const feeLabel = (data.fee && data.fee.label) ? data.fee.label : '—';
        // Checkomatic fee is member-entered — don't show a misleading $0.00/mo.
        amountLabel.textContent = _isCheckomatic ? 'Enter your monthly amount below' : feeLabel;
        monthlyWrap.style.display = _isCheckomatic ? 'block' : 'none';

        hideError(payError);
        showScreen('pay');
        initRenewCardElement();
      } catch (err) {
        console.error('[Renew] summary error:', err);
        showError(confirmError, 'Could not load renewal details. Please try again.');
      } finally {
        confirmBtn.disabled = false;
      }
    });

    // ── SCREEN 2: process payment ───────────────────────────────────────────
    payBtn?.addEventListener('click', async () => {
      if (payBtn.disabled) return;
      hideError(payError);
      payBtn.disabled = true;
      const originalLabel = payBtn.textContent;
      payBtn.textContent = 'Processing…';

      try {
        // Checkomatic monthly amount validation
        let monthlyAmount = null;
        if (_isCheckomatic && monthlyWrap.style.display !== 'none') {
          monthlyAmount = parseFloat(monthlyInput.value);
          if (!monthlyAmount || monthlyAmount <= 0) {
            showError(payError, 'Please enter a valid monthly amount.');
            return;
          }
        }

        if (!_stripe || !_cardElement) {
          showError(payError, 'Card element is not ready. Please refresh the page and try again.');
          return;
        }

        const cardholderName = (cardholderInput?.value || '').trim();
        if (!cardholderName) {
          showError(payError, 'Please enter the cardholder name.');
          cardholderInput?.focus();
          return;
        }

        // Stripe createPaymentMethod — mirrors signup page
        const { paymentMethod, error: pmError } = await _stripe.createPaymentMethod({
          type: 'card',
          card: _cardElement,
          billing_details: { name: cardholderName },
        });
        if (pmError) {
          showError(payError, pmError.message);
          return;
        }

        async function postRenewal() {
          const resp = await fetch(renewUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': renewCsrf,
            },
            body: JSON.stringify({
              payment_method_id: paymentMethod.id,
              monthly_amount: monthlyAmount,
            }),
          });
          return resp.json();
        }

        let data = await postRenewal();

        if (data && data.success === false) {
          showError(payError, data.message || 'Renewal payment failed. Please try again.');
          return;
        }

        // 3DS — confirm the card, then finalize the EXISTING renewal (no second charge).
        if (data && data.requires_action) {
          const authResult = await _stripe.confirmCardPayment(data.client_secret);
          if (authResult.error) {
            showError(payError, authResult.error.message || 'Card authentication was not completed.');
            return;
          }
          if (!authResult.paymentIntent || authResult.paymentIntent.status !== 'succeeded') {
            showError(payError, 'Card authentication did not complete successfully.');
            return;
          }
          // 3DS passed — finalize the EXISTING renewal via the dedicated endpoint.
          const finRes = await fetch(finalizeUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': renewCsrf,
            },
            body: JSON.stringify({
              renewal_id: data.renewal_id,
              payment_intent_id: data.payment_intent_id,
            }),
          });
          const finData = await finRes.json();
          if (!finData || finData.success === false) {
            showError(payError, (finData && finData.message) || 'Renewal could not be finalized.');
            return;
          }
          await goToSuccess(finData.renewal_id);
          return;
        }

        if (data && data.success) {
          await goToSuccess(data.renewal_id);
          return;
        }

        showError(payError, 'Unexpected response. Please try again.');
      } catch (err) {
        console.error('[Renew] payment error:', err);
        showError(payError, 'Network error. Please try again.');
      } finally {
        payBtn.disabled = false;
        payBtn.textContent = originalLabel;
      }
    });

    // ── SCREEN 3: poll renewal status ───────────────────────────────────────
    async function goToSuccess(renewalId) {
      showScreen('success');
      invoiceLabel.textContent = 'Processing your invoice…';

      if (!renewalId) {
        invoiceLabel.textContent = 'Renewal received — finalizing…';
        return;
      }

      const statusUrl = statusUrlBase + '/' + renewalId;
      const maxAttempts = 8;

      for (let i = 0; i < maxAttempts; i++) {
        await new Promise(r => setTimeout(r, 1500));
        try {
          const res = await fetch(statusUrl, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': renewCsrf },
          });
          const data = await res.json();
          if (data && data.processed === true) {
            invoiceLabel.textContent = data.wa_invoice_id
              ? ('Invoice Generated: INV-' + data.wa_invoice_id)
              : 'Invoice generated successfully.';
            return;
          }
          if (data && data.status === 'failed') {
            invoiceLabel.textContent = 'Your payment was received, but we could not finalize your membership automatically. Please contact ISGH support.';
            return;
          }
        } catch (err) {
          console.warn('[Renew] status poll error:', err);
        }
      }
      // Timed out without `processed`
      invoiceLabel.textContent = 'Renewal received — finalizing…';
    }
  })();
</script>
@endunless
