# Invoice Detail Modal — Design

**Date:** 2026-05-19
**Status:** Approved

## Problem

In the member portal's Payments & Invoice page, the Invoice History table has a
green "View" action per row. Today that action does nothing useful: rows render
either a disabled `<span>` or an external `<a href>` to the raw WildApricot
invoice URL. Clicking it never opens an in-app detail view.

The requirement is an in-app **invoice detail modal**, styled to match the
provided mockup, populated with live data fetched from WildApricot's invoice
API.

## Goal

Clicking "View" on any invoice row opens a modal that shows that invoice's
details, fetched live from WildApricot, with payment card details sourced from
our local Stripe records.

## Scope

In scope:
- A new WildApricot service method to fetch a single invoice by ID.
- A new authenticated JSON endpoint returning normalized invoice detail.
- An ownership guard so a member can only view their own invoices.
- The modal markup, styling, and JS in the payments page.
- A feature test for the endpoint.

Out of scope:
- Downloading/printing invoices as PDF.
- Editing or paying invoices from the modal.
- Any change to the Invoice History table columns or pagination.

## Architecture

### 1. Backend — fetch invoice detail

**`WildApricotService::getInvoiceById(int $invoiceId): ?array`**

New method following the existing `getInvoicesForContact()` pattern:
`GET /accounts/{accountId}/invoices/{invoiceId}` via the private `apiGet()`
helper. Returns the WildApricot invoice array, or `null` on any failure
(non-2xx, exception). Failures are logged like the sibling methods.

**`MemberPortalController::invoiceDetail(Request, MemberPortalService, WildApricotService)`**

New JSON endpoint:

1. Resolve `member_portal_contact_id` from the session. Missing → 401 JSON.
2. **Ownership guard:** load the member's cached bundle via
   `MemberPortalService::getBundle()`, wrap in `MemberProfile`, and confirm the
   requested invoice ID appears in `$profile->invoices`. If not → 404 JSON.
   This prevents a member viewing another member's invoice by guessing IDs.
3. Fetch the WA invoice via `getInvoiceById()`. `null` → 404/502 JSON.
4. Cross-reference local Stripe records: query `Renewal` then `LevelChange`
   for a row whose `wa_invoice_id` matches the invoice ID. The first match
   supplies `card_brand`, `card_last4`, `payment_method`, `paid_at`.
5. Return a normalized JSON payload (see Data Contract below).

**Route:** `GET /member-portal/invoice/{invoiceId}` inside the existing
`member.portal.auth` middleware group in `routes/web.php`, named
`member-portal.invoice-detail`. `{invoiceId}` constrained to digits.

### 2. Frontend — the modal

In `resources/views/member-portal/payments.blade.php`:

- Replace the View cell. For every invoice that has an `id`, render:
  `<button class="inv-view" data-invoice-id="{{ $inv['id'] }}">…View</button>`.
  Invoices with no ID keep the existing disabled `<span>`.
- Add modal markup + CSS matching the mockup:
  - Overlay with centered rounded card.
  - Header: "Invoice Details" title, subtitle, close (×) button.
  - Green gradient banner: "Invoice Number" label, large number, a "Paid"
    pill (top-right), issue date and billing period.
  - "Member Information" section: grey rounded table — Member Name,
    Membership Type.
  - "Payment Details" section: grey rounded table — Date, Payment Method,
    Payment Date, then a divider and "Total Amount Paid".
  - Amber note footer.
- JS: clicking a View button `fetch()`es the endpoint, shows a loading state
  in the modal while in flight, then populates and reveals it. On failure,
  show an inline error message inside the modal. Close on ×, overlay click,
  and Escape.

### 3. Unpaid / voided invoices

Per the approved decision: the modal only shows the Payment Details section
for **paid** invoices. For an unpaid or voided invoice, that section is
replaced by a status block showing the invoice status (e.g. "Unpaid") and the
amount due. The "Paid" pill is hidden or replaced with the actual status.

## Data Contract

The endpoint returns JSON:

```json
{
  "success": true,
  "invoice": {
    "number": "INV-2025-0156",
    "issueDate": "2026-01-15",
    "billingPeriod": "Jan 2026 – Jan 2027",
    "isPaid": true,
    "status": "Paid",
    "amount": 20.00,
    "currency": "USD",
    "memberName": "Tauqeer Alam",
    "membershipType": "Individual Membership",
    "payment": {
      "date": "2026-01-15",
      "method": "Credit Card (**** 4242)",
      "paymentDate": "2026-01-15"
    }
  }
}
```

`payment` is `null` when the invoice is not paid. When the invoice is paid but
no local Stripe record matches, `method` falls back to a generic label
(e.g. "Credit Card" / "Online Payment") with no last-4, and dates come from
the WA invoice's own payment data.

## Data Sources

- **Invoice number, issue date, paid status, amount, currency:** WA invoice
  (`DocumentNumber`, `CreatedDate`, `IsPaid`, `Value`).
- **Member name, membership type:** the `$profile` already rendered on the
  page — the invoice belongs to the logged-in member.
- **Billing period:** reuse `MemberProfile::billingPeriod()` logic.
- **Card brand / last-4, payment method, payment date:** matched local
  `Renewal` / `LevelChange` row by `wa_invoice_id`; fallback to WA invoice
  payment data with a generic label.

## Error Handling

- No session contact → 401 JSON, modal shows "Please sign in again."
- Invoice not owned by member → 404 JSON, modal shows "Invoice not found."
- WA fetch fails → 404/502 JSON, modal shows "Could not load invoice details."
- All server-side failures logged via `Log::warning` / `Log::error`, matching
  the existing service/controller conventions.

## Testing

A feature test for `invoiceDetail`, with WA HTTP calls faked via `Http::fake()`:

- Ownership guard: requesting an invoice ID not in the member's bundle → 404.
- Paid invoice with a matching `Renewal` row → response includes `payment`
  with the card brand and last-4.
- Unpaid invoice → `payment` is `null`, status reflects the unpaid state.
- No session → 401.
