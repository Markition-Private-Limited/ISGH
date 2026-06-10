{{--
  Level-change modal — shared by the member dashboard and profile pages.
  Self-contained: carries its own CSS, HTML, and JS, plus the Stripe.js
  <script> tag (both modals need Stripe; this modal is ungated and always
  rendered, so it is the right home for the unconditional Stripe load).
  The .lvl-* CSS depends on the shared base modal classes (.renew-overlay,
  .renew-modal, etc.) provided by the renew-modal partial — always include
  the renew-modal partial alongside this one. The level-change JS IIFE
  early-returns when #levelModal is absent, so including this partial is safe.
--}}
<style>
    /* ── Level-change modal (reuses .renew-* overlay/card/button styles) ── */
    /* Current membership display */
    .lvl-current {
      display: flex; flex-direction: column; gap: 3px;
      background: #f6f8f9;
      border: 1px solid #e6ebe8;
      border-radius: var(--radius-sm);
      padding: 12px 14px;
      margin: 16px 0 14px;
    }
    .lvl-current-tag {
      font-size: 11px; font-weight: 600; letter-spacing: .03em;
      text-transform: uppercase; color: var(--text-muted);
    }
    .lvl-current-name { font-size: 14px; font-weight: 700; color: var(--text); }
    .lvl-current-fee { font-size: 13px; font-weight: 600; color: var(--green); }

    /* New-level select dropdown */
    .lvl-select-field { margin-bottom: 14px; text-align: left; }
    .lvl-select-field label {
      display: block; font-size: 12px; font-weight: 600;
      color: #475569; margin-bottom: 6px;
    }
    .lvl-select-field select {
      width: 100%;
      background: #fff;
      border: 1px solid #e6ebe8;
      border-radius: 10px;
      padding: 11px 12px;
      font-size: 14px; color: var(--text);
      font-family: inherit; outline: none;
      cursor: pointer;
      transition: border-color .15s, box-shadow .15s;
    }
    .lvl-select-field select:focus {
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(13,122,82,0.12);
    }

    /* Checkomatic amber amount box */
    .lvl-amount-box {
      display: flex; flex-direction: column; gap: 6px;
      background: #fffbeb;
      border: 1px solid #fcd34d;
      border-radius: var(--radius-sm);
      padding: 13px 14px;
      margin-bottom: 14px;
      text-align: left;
    }
    .lvl-amount-title { font-size: 13px; font-weight: 700; color: #92400e; }
    .lvl-amount-box input {
      width: 100%;
      background: #fff;
      border: 1px solid #fcd34d;
      border-radius: 8px;
      padding: 9px 11px;
      font-size: 14px; color: var(--text);
      font-family: inherit; outline: none;
      transition: border-color .15s, box-shadow .15s;
    }
    .lvl-amount-box input:focus {
      border-color: #d97706;
      box-shadow: 0 0 0 3px rgba(217,119,6,0.15);
    }
    .lvl-amount-hint { font-size: 11.5px; color: #b45309; }
    .lvl-amount-note {
      font-size: 12px;
      color: #92400e;
      margin: 4px 0 0;
      font-weight: 600;
    }
    .lvl-amount-error {
      color: #dc2626 !important;
      font-size: 12px;
    }
    .lvl-checkomatic-warning {
      background: #fffbeb;
      border: 1px solid #fcd34d;
      border-radius: var(--radius-sm);
      padding: 11px 13px;
      margin-bottom: 14px;
      font-size: 12.5px;
      color: #92400e;
      line-height: 1.5;
    }
    .lvl-checkomatic-warning p { margin: 0; }

    /* Review screen — from/to cards */
    .lvl-review-cards {
      display: flex; align-items: stretch; gap: 8px;
      margin: 16px 0 14px;
    }
    .lvl-review-card {
      flex: 1;
      display: flex; flex-direction: column; gap: 3px;
      background: #f6f8f9;
      border: 1px solid #e6ebe8;
      border-radius: var(--radius-sm);
      padding: 12px;
      text-align: left;
    }
    .lvl-review-card.to { border-color: var(--green); background: #f0faf5; }
    .lvl-review-card-tag {
      font-size: 10.5px; font-weight: 600; letter-spacing: .03em;
      text-transform: uppercase; color: var(--text-muted);
    }
    .lvl-review-card-name { font-size: 13.5px; font-weight: 700; color: var(--text); }
    .lvl-review-card-fee { font-size: 12.5px; font-weight: 600; color: var(--green); }
    .lvl-review-arrow {
      display: flex; align-items: center; color: var(--text-muted);
    }
    .lvl-review-arrow svg { width: 18px; height: 18px; stroke-width: 2.2; }

    /* Review screen — green payment details panel */
    .lvl-pay-panel {
      background: #f0faf5;
      border: 1px solid #bbe8d2;
      border-radius: var(--radius-sm);
      padding: 14px;
      margin-bottom: 6px;
      text-align: left;
    }
    .lvl-pay-panel-title {
      display: block;
      font-size: 13px; font-weight: 700; color: var(--green);
      margin-bottom: 10px;
    }
    .lvl-pay-line {
      display: flex; align-items: center; justify-content: space-between;
      font-size: 13px; color: var(--text);
      padding: 5px 0;
    }
    .lvl-pay-line span:last-child { font-weight: 600; }
    .lvl-pay-line-total {
      margin-top: 4px; padding-top: 9px;
      border-top: 1px solid #bbe8d2;
      font-weight: 700;
    }
    .lvl-pay-line-total span:last-child { color: var(--green); font-size: 15px; }
    .lvl-family-block {
      background: #f6f8f9;
      border-radius: var(--radius-sm);
      padding: 14px 14px 6px;
      margin-bottom: 12px;
      position: relative;
    }
    .lvl-family-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    }
    .lvl-family-block .lvl-field-full { grid-column: 1 / -1; }
    .lvl-family-block label {
      display: block; font-size: 11px; font-weight: 500;
      color: #475569; margin-bottom: 4px;
    }
    .lvl-family-block input {
      width: 100%;
      background: #fff;
      border: 1px solid #e6ebe8;
      border-radius: 8px;
      padding: 9px 11px;
      font-size: 13px; color: var(--text);
      font-family: inherit; outline: none;
      transition: border-color .15s, box-shadow .15s;
      margin-bottom: 10px;
    }
    .lvl-family-block input:focus {
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(13,122,82,0.12);
    }
    /* Invalid family-member field (duplicate email/phone/name+DOB). */
    .lvl-family-block input.lvl-invalid {
      border-color: #dc2626 !important;
      box-shadow: 0 0 0 3px rgba(220,38,38,0.12) !important;
    }
    .lvl-field-msg {
      font-size: 11px;
      margin: -6px 0 8px;
      min-height: 13px;
      line-height: 1.3;
    }
    .lvl-field-msg.error   { color: #dc2626; }
    .lvl-field-msg.success { color: #15803d; }
    .lvl-family-remove {
      position: absolute; top: 10px; right: 10px;
      width: 26px; height: 26px;
      border: none; background: #fee2e2; color: #b91c1c;
      border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
      transition: background .15s;
    }
    .lvl-family-remove:hover { background: #fecaca; }
    .lvl-family-remove svg { width: 13px; height: 13px; stroke-width: 2.4; }
    .lvl-add-member {
      width: 100%;
      background: #fff;
      border: 1px dashed #cbd5e1;
      color: var(--green);
      font-size: 13px; font-weight: 600;
      padding: 10px;
      border-radius: 10px;
      transition: background .15s, border-color .15s;
    }
    .lvl-add-member:hover { background: #f1f5f4; border-color: var(--green); }
    .lvl-family-empty {
      font-size: 12px; color: var(--text-muted);
      text-align: center; padding: 8px 0 12px;
    }
</style>
{{-- Stripe.js — loaded unconditionally; both the renewal and level-change
     modals need it, and the level-change modal renders for all members
     (including lifetime members, whose renewal modal is omitted). --}}
<script src="https://js.stripe.com/v3/"></script>

{{-- ── Level-change modal ── --}}
<div class="renew-overlay" id="levelModal" aria-hidden="true">
  <div class="renew-modal" role="dialog" aria-modal="true">
    <button type="button" class="renew-close" id="lvlCloseBtn" aria-label="Close">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>

    {{-- SCREEN 1 — pick a level --}}
    <div class="renew-screen active" id="lvlScreenPick">
      <div class="renew-icon-circle ok">
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
      </div>
      <h3>Change Membership Level</h3>
      <p class="renew-sub">Select the membership level you would like to switch to. You can review the fee before paying.</p>

      <div class="lvl-current" id="lvlCurrentBox">
        <span class="lvl-current-tag">Current Membership</span>
        <span class="lvl-current-name" id="lvlCurrentName">—</span>
        <span class="lvl-current-fee" id="lvlCurrentFee">—</span>
      </div>

      <div class="lvl-select-field">
        <label for="lvlSelect">Select New Membership Level</label>
        <select id="lvlSelect">
          <option value="">Loading available levels…</option>
        </select>
      </div>

      {{-- Checkomatic amount entry — shown only when checkomatic level is selected --}}
      <div class="lvl-amount-box" id="lvlAmountBox" style="display:none;">
        <span class="lvl-amount-title">Monthly Amount (Minimum $10)</span>
        <input id="lvlMonthlyInput" type="number" min="10" step="1" value="10" placeholder="Minimum $10.00" />
        <p id="lvlAmountNote" class="lvl-amount-note">You will be charged $10.00/month</p>
        <span id="lvlAmountError" class="lvl-amount-hint lvl-amount-error" style="display:none;">Minimum monthly amount is $10.00.</span>
      </div>

      {{-- Recurring-billing warning — shown below amount box when Checkomatic is selected --}}
      <div class="lvl-checkomatic-warning" id="lvlCheckomaticWarning" style="display:none;">
        <p>To qualify as a voting member for the current year, a minimum membership contribution of $20/person must be completed by June 30.</p>
      </div>

      <div class="renew-error" id="lvlPickError"></div>
      <div class="renew-actions">
        <button type="button" class="renew-btn renew-btn-ghost" id="lvlPickCancel">Cancel</button>
        <button type="button" class="renew-btn renew-btn-primary" id="lvlPickNext" disabled>Continue</button>
      </div>
    </div>

    {{-- SCREEN 2 — add family members --}}
    <div class="renew-screen" id="lvlScreenFamily">
      <h3 id="lvlFamilyHeading">Add Family Members</h3>
      <p class="renew-sub" id="lvlFamilySub">This level includes family members. Add the people you would like covered under your membership.</p>
      <div id="lvlFamilyContainer"></div>
      <button type="button" class="lvl-add-member" id="lvlAddMember">+ Add Member</button>
      <div class="renew-error" id="lvlFamilyError"></div>
      <div class="renew-actions">
        <button type="button" class="renew-btn renew-btn-ghost" id="lvlFamilyBack">Back</button>
        <button type="button" class="renew-btn renew-btn-primary" id="lvlFamilyNext">Continue</button>
      </div>
    </div>

    {{-- SCREEN 3 — confirm / review --}}
    <div class="renew-screen" id="lvlScreenReview">
      <h3>Confirm Membership Change</h3>
      <p class="renew-sub">Please review your membership change before continuing to payment.</p>

      <div class="lvl-review-cards">
        <div class="lvl-review-card">
          <span class="lvl-review-card-tag">From</span>
          <span class="lvl-review-card-name" id="lvlReviewFromName">—</span>
          <span class="lvl-review-card-fee" id="lvlReviewFromFee">—</span>
        </div>
        <div class="lvl-review-arrow">
          <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
          </svg>
        </div>
        <div class="lvl-review-card to">
          <span class="lvl-review-card-tag">To</span>
          <span class="lvl-review-card-name" id="lvlReviewToName">—</span>
          <span class="lvl-review-card-fee" id="lvlReviewToFee">—</span>
        </div>
      </div>

      <div class="lvl-pay-panel">
        <span class="lvl-pay-panel-title">Payment Details</span>
        <div class="lvl-pay-line">
          <span>Current Contribution</span>
          <span id="lvlReviewCurrent">—</span>
        </div>
        <div class="lvl-pay-line">
          <span>New Contribution</span>
          <span id="lvlReviewNew">—</span>
        </div>
        <div class="lvl-pay-line lvl-pay-line-total">
          <span>Amount Due Now</span>
          <span id="lvlReviewDue">—</span>
        </div>
      </div>

      <div class="renew-actions">
        <button type="button" class="renew-btn renew-btn-ghost" id="lvlReviewBack">Back</button>
        <button type="button" class="renew-btn renew-btn-primary" id="lvlReviewNext">Continue to Payment</button>
      </div>
    </div>

    {{-- SCREEN 4 — payment --}}
    <div class="renew-screen" id="lvlScreenPay">
      <h3>Complete Payment</h3>
      <p class="renew-sub">Enter your card details to complete your level change securely.</p>

      <div class="renew-pay-row">
        <span class="label">Pending Payment</span>
        <span class="amount" id="lvlAmountLabel">—</span>
      </div>

      <label class="renew-card-label" for="lvl-card-element">Card details</label>
      <div id="lvl-card-element"></div>

      <div class="renew-error" id="lvlPayError"></div>

      <div class="renew-actions">
        <button type="button" class="renew-btn renew-btn-ghost" id="lvlPayBack">Back</button>
        <button type="button" class="renew-btn renew-btn-primary" id="lvlPayBtn">Pay</button>
      </div>
    </div>

    {{-- SCREEN 5 — success --}}
    <div class="renew-screen" id="lvlScreenSuccess">
      <div class="renew-icon-circle ok">
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>
      <h3>Level Changed!</h3>
      <p class="renew-sub">Thank you. Your membership level update is being processed.</p>
      <div class="renew-invoice" id="lvlSuccessLabel">Processing your level change…</div>
      <div class="renew-actions">
        <button type="button" class="renew-btn renew-btn-primary" id="lvlDoneBtn">Go to Dashboard</button>
      </div>
    </div>
  </div>
</div>

{{-- family-member block template --}}
<template id="lvlFamilyTemplate">
  <div class="lvl-family-block">
    <button type="button" class="lvl-family-remove" aria-label="Remove member">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
    <div class="lvl-family-grid">
      <div>
        <label>First Name</label>
        <input type="text" data-field="first_name" placeholder="First name" />
      </div>
      <div>
        <label>Last Name</label>
        <input type="text" data-field="last_name" placeholder="Last name" />
      </div>
      <div class="lvl-field-full">
        <label>Email</label>
        <input type="email" data-field="email" placeholder="Email address" />
        <div class="lvl-field-msg" data-msg-for="email"></div>
      </div>
      <div>
        <label>Phone</label>
        <input type="tel" data-field="phone" placeholder="Phone" />
        <div class="lvl-field-msg" data-msg-for="phone"></div>
      </div>
      <div>
        <label>Date of Birth</label>
        <input type="date" data-field="dob" />
      </div>
      <div class="lvl-field-full">
        <label>TX DL # / TX ID #</label>
        <input type="text" data-field="tx_dl" placeholder="e.g. TX7234578" />
      </div>
    </div>
  </div>
</template>

<script>
  // ── Level-change modal module (self-contained IIFE) ─────────────────────
  (function () {
    const modal = document.getElementById('levelModal');
    if (!modal) return;

    const lvlCsrf      = document.querySelector('meta[name="csrf-token"]').content;
    const lvlStripeKey = '{{ $stripePublishableKey ?? config("services.stripe.key") }}';

    const optionsUrl    = '{{ route('member-portal.change-level.options') }}';
    const changeUrl     = '{{ route('member-portal.change-level') }}';
    const finalizeUrl   = '{{ route('member-portal.change-level.finalize') }}';
    const statusUrlBase = '{{ url('/member-portal/change-level/status') }}';
    const dashboardUrl  = '{{ route('member-portal.dashboard') }}';

    const screens = {
      pick:    document.getElementById('lvlScreenPick'),
      family:  document.getElementById('lvlScreenFamily'),
      review:  document.getElementById('lvlScreenReview'),
      pay:     document.getElementById('lvlScreenPay'),
      success: document.getElementById('lvlScreenSuccess'),
    };
    const closeBtn      = document.getElementById('lvlCloseBtn');
    const currentName   = document.getElementById('lvlCurrentName');
    const currentFee    = document.getElementById('lvlCurrentFee');
    const levelSelect   = document.getElementById('lvlSelect');
    const amountBox     = document.getElementById('lvlAmountBox');
    const monthlyInput  = document.getElementById('lvlMonthlyInput');
    const amountNote    = document.getElementById('lvlAmountNote');
    const amountError   = document.getElementById('lvlAmountError');
    const checkWarning  = document.getElementById('lvlCheckomaticWarning');
    const pickError     = document.getElementById('lvlPickError');
    const pickCancel    = document.getElementById('lvlPickCancel');
    const pickNext      = document.getElementById('lvlPickNext');
    const familyBox     = document.getElementById('lvlFamilyContainer');
    const familyHeading = document.getElementById('lvlFamilyHeading');
    const familySub     = document.getElementById('lvlFamilySub');
    const addMemberBtn  = document.getElementById('lvlAddMember');
    const familyBack    = document.getElementById('lvlFamilyBack');
    const familyNext    = document.getElementById('lvlFamilyNext');
    const familyTpl     = document.getElementById('lvlFamilyTemplate');
    const familyError   = document.getElementById('lvlFamilyError');
    const reviewFromName = document.getElementById('lvlReviewFromName');
    const reviewFromFee  = document.getElementById('lvlReviewFromFee');
    const reviewToName   = document.getElementById('lvlReviewToName');
    const reviewToFee    = document.getElementById('lvlReviewToFee');
    const reviewCurrent  = document.getElementById('lvlReviewCurrent');
    const reviewNew      = document.getElementById('lvlReviewNew');
    const reviewDue      = document.getElementById('lvlReviewDue');
    const reviewBack     = document.getElementById('lvlReviewBack');
    const reviewNext     = document.getElementById('lvlReviewNext');
    const amountLabel   = document.getElementById('lvlAmountLabel');
    const payBack       = document.getElementById('lvlPayBack');
    const payBtn        = document.getElementById('lvlPayBtn');
    const payError      = document.getElementById('lvlPayError');
    const successLabel  = document.getElementById('lvlSuccessLabel');
    const doneBtn       = document.getElementById('lvlDoneBtn');

    let _stripe = null;
    let _cardElement = null;
    let _cardMounted = false;
    let _optionsLoaded = false;
    let _current = null;        // {type, label, feeLabel} — member's current level
    let _levels = [];           // available target levels
    let _selected = null;       // selected level object
    let _isCheckomatic = false;
    let _payAmountLabel = '—';  // resolved fee label shown on review + pay screens

    function showError(el, msg) { if (el) { el.textContent = msg; el.classList.add('show'); } }
    function hideError(el)      { if (el) { el.textContent = ''; el.classList.remove('show'); } }

    function updateLvlAmountNote() {
      if (!monthlyInput || !amountNote) return;
      const val = parseFloat(monthlyInput.value);
      const min = 10;
      if (isNaN(val) || val < min) {
        amountNote.style.display = 'none';
        if (amountError) { amountError.style.display = ''; }
        pickNext.disabled = true;
      } else {
        amountNote.textContent = 'You will be charged $' + val.toFixed(2) + '/month';
        amountNote.style.display = '';
        if (amountError) { amountError.style.display = 'none'; }
        pickNext.disabled = !_selected;
      }
    }

    function showScreen(name) {
      Object.entries(screens).forEach(([k, el]) => {
        if (el) el.classList.toggle('active', k === name);
      });
    }

    // ── Stripe init — mirrors the renewal modal ──────────────────────────
    function initLvlCardElement() {
      if (_cardMounted) return;
      if (!lvlStripeKey) { console.warn('[Level][Stripe] publishable key not set'); return; }
      if (typeof Stripe === 'undefined') {
        console.error('[Level][Stripe] Stripe.js has not loaded');
        showError(payError, 'Payment library failed to load. Please refresh the page and try again.');
        return;
      }
      _stripe = Stripe(lvlStripeKey);
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
      _cardElement.mount('#lvl-card-element');
      _cardElement.on('change', e => {
        if (e.error) showError(payError, e.error.message);
        else hideError(payError);
      });
      _cardMounted = true;
    }

    // ── SCREEN 1: load available levels into the select ──────────────────
    async function loadOptions() {
      if (_optionsLoaded) return;
      levelSelect.innerHTML = '<option value="">Loading available levels…</option>';
      levelSelect.disabled = true;
      try {
        const res = await fetch(optionsUrl, {
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': lvlCsrf },
        });
        const data = await res.json();
        if (!data || data.success !== true || !Array.isArray(data.levels) || data.levels.length === 0) {
          levelSelect.innerHTML = '<option value="">No alternative levels are available.</option>';
          return;
        }

        // Current membership display
        _current = data.current || null;
        if (_current) {
          currentName.textContent = _current.label || '—';
          currentFee.textContent  = _current.feeLabel || '—';
        }

        _levels = data.levels.map(lvl => ({
          type:           lvl.type,
          label:          lvl.label || lvl.type,
          feeLabel:       (lvl.fee && lvl.fee.label) ? lvl.fee.label : '—',
          includesFamily: !!lvl.includesFamily,
          isCheckomatic:  !!lvl.isCheckomatic,
        }));

        levelSelect.innerHTML = '<option value="">— Choose a membership level —</option>';
        _levels.forEach(lvl => {
          const opt = document.createElement('option');
          opt.value = lvl.type;
          opt.textContent = lvl.label;
          levelSelect.appendChild(opt);
        });
        levelSelect.disabled = false;
        _optionsLoaded = true;
      } catch (err) {
        console.error('[Level] options error:', err);
        levelSelect.innerHTML = '<option value="">Could not load levels. Please try again.</option>';
      }
    }

    // ── SCREEN 1: select-change handler ──────────────────────────────────
    function onLevelSelected() {
      const type = levelSelect.value;
      const lvl = _levels.find(l => l.type === type) || null;

      // Switching the target level discards stale family blocks.
      _selected = lvl;
      familyBox.innerHTML = '';
      _isCheckomatic = !!(lvl && lvl.isCheckomatic);

      // Checkomatic levels need a monthly amount entered here on Screen 1.
      if (_isCheckomatic) {
        amountBox.style.display = 'flex';
        if (checkWarning) checkWarning.style.display = '';
        updateLvlAmountNote();
      } else {
        amountBox.style.display = 'none';
        if (checkWarning) checkWarning.style.display = 'none';
        if (monthlyInput) monthlyInput.value = '';
      }

      pickNext.disabled = !lvl;
      hideError(pickError);
    }

    // ── Family members ───────────────────────────────────────────────────
    // `fixed` blocks (the lone spouse for checkomatic-with-spouse) have no
    // remove button so the single block can't be deleted.
    function addFamilyBlock(fixed) {
      const node = familyTpl.content.firstElementChild.cloneNode(true);
      const removeBtn = node.querySelector('.lvl-family-remove');
      if (fixed) {
        removeBtn.remove();
      } else {
        removeBtn.addEventListener('click', () => { node.remove(); });
      }
      familyBox.appendChild(node);
    }

    /** True when the target collects only a single spouse (checkomatic-with-spouse). */
    function isSpouseOnly() {
      return !!(_selected && _selected.type === 'checkomatic_family');
    }

    /** Build the family screen for the current selection. */
    function buildFamilyScreen() {
      familyBox.innerHTML = '';
      if (isSpouseOnly()) {
        familyHeading.textContent = 'Add Spouse';
        familySub.textContent = 'This level includes your spouse. Enter their details below.';
        addMemberBtn.style.display = 'none';
        addFamilyBlock(true);
      } else {
        familyHeading.textContent = 'Add Family Members';
        familySub.textContent = 'This level includes family members. Add the people you would like covered under your membership.';
        addMemberBtn.style.display = '';
        addFamilyBlock(false);
      }
    }

    function collectFamilyMembers() {
      const members = [];
      familyBox.querySelectorAll('.lvl-family-block').forEach(block => {
        const get = f => {
          const inp = block.querySelector('[data-field="' + f + '"]');
          return inp ? inp.value.trim() : '';
        };
        const m = {
          first_name: get('first_name'),
          last_name:  get('last_name'),
          email:      get('email'),
          phone:      get('phone'),
          dob:        get('dob'),
          tx_dl:      get('tx_dl'),
        };
        // Skip fully-empty blocks
        if (m.first_name || m.last_name || m.email || m.phone || m.dob || m.tx_dl) {
          members.push(m);
        }
      });
      return members;
    }

    /** Resolve the fee label to charge for the current selection. */
    function resolvePayAmountLabel() {
      if (!_selected) return '—';
      if (_selected.isCheckomatic) {
        const amt = parseFloat(monthlyInput.value);
        return (amt && amt > 0) ? '$' + amt.toFixed(2) + '/mo' : '—';
      }
      if (_selected.type === 'flat') {
        // Flat fee scales with member count ($20 each); the cached feeLabel was
        // computed for one member, so recompute from the entered family blocks.
        const memberCount = 1 + collectFamilyMembers().length;
        return '$' + (memberCount * 20).toFixed(2);
      }
      return _selected.feeLabel || '—';
    }

    // ── Enter the review screen ──────────────────────────────────────────
    function goToReview() {
      hideError(pickError);
      _payAmountLabel = resolvePayAmountLabel();

      reviewFromName.textContent = _current ? (_current.label || '—') : '—';
      reviewFromFee.textContent  = _current ? (_current.feeLabel || '—') : '—';
      reviewToName.textContent   = _selected ? _selected.label : '—';
      reviewToFee.textContent    = _payAmountLabel;

      reviewCurrent.textContent  = _current ? (_current.feeLabel || '—') : '—';
      reviewNew.textContent      = _payAmountLabel;
      reviewDue.textContent      = _payAmountLabel;

      showScreen('review');
    }

    // ── Enter the payment screen ─────────────────────────────────────────
    function goToPay() {
      hideError(payError);
      amountLabel.textContent = _payAmountLabel;
      showScreen('pay');
      initLvlCardElement();
    }

    // ── Open / close ─────────────────────────────────────────────────────
    function openModal() {
      hideError(pickError);
      hideError(payError);
      _selected = null;
      _isCheckomatic = false;
      _payAmountLabel = '—';
      pickNext.disabled = true;
      levelSelect.value = '';
      amountBox.style.display = 'none';
      if (checkWarning) checkWarning.style.display = 'none';
      if (monthlyInput) monthlyInput.value = '';
      if (successLabel) successLabel.textContent = '';
      familyBox.innerHTML = '';
      showScreen('pick');
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      loadOptions();
    }

    function closeModal() {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    document.querySelectorAll('.ql-change-level').forEach(el => {
      el.addEventListener('click', e => {
        e.preventDefault();
        openModal();
      });
    });

    closeBtn?.addEventListener('click', closeModal);
    pickCancel?.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    doneBtn?.addEventListener('click', () => { location.href = dashboardUrl; });

    levelSelect?.addEventListener('change', onLevelSelected);
    monthlyInput?.addEventListener('input', updateLvlAmountNote);

    // ── SCREEN 1 → family / review ───────────────────────────────────────
    pickNext?.addEventListener('click', () => {
      if (pickNext.disabled || !_selected) return;
      // Checkomatic levels need a valid monthly amount before continuing.
      if (_selected.isCheckomatic) {
        const amt = parseFloat(monthlyInput.value);
        if (!amt || amt < 10) {
          showError(pickError, 'Please enter an amount of at least $10.00.');
          return;
        }
      }
      if (_selected.includesFamily) {
        buildFamilyScreen();
        showScreen('family');
      } else {
        goToReview();
      }
    });

    // ── SCREEN 2 (family) navigation ─────────────────────────────────────
    familyBack?.addEventListener('click', () => { showScreen('pick'); });
    addMemberBtn?.addEventListener('click', () => { addFamilyBlock(false); });

    // ── Family-member duplicate validation ───────────────────────────────
    // Same checks as member creation: email-exists, phone-exists, name+DOB.
    // Runs in real time (after typing) and again on Continue. The WA lookups
    // reuse the membership check-email / check-phone / verify endpoints.
    const _lvlCheckEmailUrl = '{{ route('membership.check-email') }}';
    const _lvlCheckPhoneUrl = '{{ route('membership.check-phone') }}';
    const _lvlVerifyUrl     = '{{ route('membership.verify') }}';
    const _lvlDebounce      = {};

    function lvlInput(block, field) {
      return block.querySelector('[data-field="' + field + '"]');
    }
    function lvlMsg(block, field) {
      return block.querySelector('[data-msg-for="' + field + '"]');
    }
    function lvlSetMsg(block, field, text, kind) {
      const inp = lvlInput(block, field);
      const msg = lvlMsg(block, field);
      if (inp) inp.classList.toggle('lvl-invalid', kind === 'error');
      if (msg) {
        msg.textContent = text || '';
        msg.className = 'lvl-field-msg' + (kind ? ' ' + kind : '');
      }
    }
    // Clears a field's error styling (used when the user edits it).
    function lvlClearField(block, field) {
      const inp = lvlInput(block, field);
      if (inp) { inp.classList.remove('lvl-invalid'); inp._lvlDupe = false; }
      const msg = lvlMsg(block, field);
      if (msg) { msg.textContent = ''; msg.className = 'lvl-field-msg'; }
    }

    async function lvlCheckEmail(block) {
      const inp = lvlInput(block, 'email');
      if (!inp) return;
      const email = inp.value.trim();
      inp._lvlDupe = false;
      if (!email) { lvlSetMsg(block, 'email', '', null); return; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        lvlSetMsg(block, 'email', 'Enter a valid email address.', 'error');
        inp._lvlDupe = true;
        return;
      }
      lvlSetMsg(block, 'email', 'Checking…', null);
      try {
        const res = await fetch(_lvlCheckEmailUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': lvlCsrf, 'Accept': 'application/json' },
          body: JSON.stringify({ email }),
        });
        const data = await res.json();
        if (data.exists) {
          inp._lvlDupe = true;
          lvlSetMsg(block, 'email', 'This email is already registered as an ISGH member.', 'error');
        } else {
          lvlSetMsg(block, 'email', '✓ Email is available', 'success');
        }
      } catch (e) {
        // Network error — don't block; clear the checking state.
        lvlSetMsg(block, 'email', '', null);
      }
    }

    async function lvlCheckPhone(block) {
      const inp = lvlInput(block, 'phone');
      if (!inp) return;
      const phone = inp.value.trim();
      inp._lvlDupe = false;
      if (!phone) { lvlSetMsg(block, 'phone', '', null); return; }
      if (phone.replace(/\D/g, '').length < 10) {
        lvlSetMsg(block, 'phone', 'Enter a valid 10-digit phone number.', 'error');
        inp._lvlDupe = true;
        return;
      }
      lvlSetMsg(block, 'phone', 'Checking…', null);
      try {
        const res = await fetch(_lvlCheckPhoneUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': lvlCsrf, 'Accept': 'application/json' },
          body: JSON.stringify({ phone }),
        });
        const data = await res.json();
        if (data.exists) {
          inp._lvlDupe = true;
          lvlSetMsg(block, 'phone', 'This phone is already registered as an ISGH member.', 'error');
        } else {
          lvlSetMsg(block, 'phone', '✓ Phone is available', 'success');
        }
      } catch (e) {
        lvlSetMsg(block, 'phone', '', null);
      }
    }

    // Real-time: debounced email/phone checks; clear field error as user edits.
    familyBox.addEventListener('input', e => {
      const inp = e.target;
      const field = inp.dataset ? inp.dataset.field : null;
      if (!field) return;
      const block = inp.closest('.lvl-family-block');
      if (!block) return;
      lvlClearField(block, field);                 // editing clears prior error
      hideError(familyError);
      if (field === 'email' || field === 'phone') {
        const key = field + '_' + (block._lvlId || (block._lvlId = Math.random()));
        clearTimeout(_lvlDebounce[key]);
        _lvlDebounce[key] = setTimeout(() => {
          field === 'email' ? lvlCheckEmail(block) : lvlCheckPhone(block);
        }, 600);
      }
    });
    // Immediate check on blur (covers paste-and-tab).
    familyBox.addEventListener('blur', e => {
      const inp = e.target;
      const field = inp.dataset ? inp.dataset.field : null;
      const block = inp.closest && inp.closest('.lvl-family-block');
      if (!block) return;
      if (field === 'email') lvlCheckEmail(block);
      if (field === 'phone') lvlCheckPhone(block);
    }, true);

    // Submit-time validation — re-checks email/phone and runs the name+DOB
    // duplicate check for every family block. Reddens offending fields and
    // returns false to block the Continue step.
    async function validateFamilyOnContinue() {
      hideError(familyError);
      const blocks = [...familyBox.querySelectorAll('.lvl-family-block')];
      let ok = true;
      let firstBad = null;

      for (const block of blocks) {
        const first = (lvlInput(block, 'first_name')?.value || '').trim();
        const last  = (lvlInput(block, 'last_name')?.value || '').trim();
        const dob   = (lvlInput(block, 'dob')?.value || '').trim();

        // Re-run email/phone existence checks so a freshly-typed value is caught.
        await lvlCheckEmail(block);
        await lvlCheckPhone(block);
        if (lvlInput(block, 'email')?._lvlDupe) { ok = false; firstBad = firstBad || lvlInput(block, 'email'); }
        if (lvlInput(block, 'phone')?._lvlDupe) { ok = false; firstBad = firstBad || lvlInput(block, 'phone'); }

        // Name + DOB combination duplicate (only when a name is entered).
        if (first) {
          try {
            const res = await fetch(_lvlVerifyUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': lvlCsrf, 'Accept': 'application/json' },
              body: JSON.stringify({ first_name: first, last_name: last, date_of_birth: dob }),
            });
            const data = await res.json();
            // verifyMembership returns success:true when a matching member exists.
            if (data.success) {
              ok = false;
              ['first_name', 'last_name', 'dob'].forEach(f => {
                const i = lvlInput(block, f);
                if (i) i.classList.add('lvl-invalid');
              });
              firstBad = firstBad || lvlInput(block, 'first_name');
            }
          } catch (e) { /* network error — don't block on it */ }
        }
      }

      if (!ok) {
        showError(familyError, 'One or more family members are already registered with ISGH. Please review the highlighted fields.');
        if (firstBad) firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      return ok;
    }

    familyNext?.addEventListener('click', async () => {
      const orig = familyNext.textContent;
      familyNext.disabled = true;
      familyNext.textContent = 'Checking…';
      try {
        if (await validateFamilyOnContinue()) goToReview();
      } finally {
        familyNext.disabled = false;
        familyNext.textContent = orig;
      }
    });

    // ── SCREEN 3 (review) navigation ─────────────────────────────────────
    reviewBack?.addEventListener('click', () => {
      showScreen(_selected && _selected.includesFamily ? 'family' : 'pick');
    });
    reviewNext?.addEventListener('click', () => { goToPay(); });

    // ── SCREEN 4 (pay) navigation ────────────────────────────────────────
    payBack?.addEventListener('click', () => {
      hideError(payError);
      showScreen('review');
    });

    // ── SCREEN 3: process payment ────────────────────────────────────────
    payBtn?.addEventListener('click', async () => {
      if (payBtn.disabled) return;
      hideError(payError);
      payBtn.disabled = true;
      const originalLabel = payBtn.textContent;
      payBtn.textContent = 'Processing…';

      try {
        if (!_selected) {
          showError(payError, 'Please select a level first.');
          return;
        }

        // Checkomatic monthly amount — entered on Screen 1; re-validate here.
        let monthlyAmount = null;
        if (_isCheckomatic) {
          monthlyAmount = parseFloat(monthlyInput.value);
          if (!monthlyAmount || monthlyAmount < 10) {
            showError(payError, 'Please enter an amount of at least $10.00 on the first step.');
            return;
          }
        }

        if (!_stripe || !_cardElement) {
          showError(payError, 'Card element is not ready. Please refresh the page and try again.');
          return;
        }

        // Stripe createPaymentMethod — mirrors the renewal modal
        const { paymentMethod, error: pmError } = await _stripe.createPaymentMethod({
          type: 'card',
          card: _cardElement,
        });
        if (pmError) {
          showError(payError, pmError.message);
          return;
        }

        const familyMembers = _selected.includesFamily ? collectFamilyMembers() : [];

        async function postChange() {
          const resp = await fetch(changeUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': lvlCsrf,
            },
            body: JSON.stringify({
              target_type: _selected.type,
              payment_method_id: paymentMethod.id,
              monthly_amount: monthlyAmount,
              family_members: familyMembers,
            }),
          });
          return resp.json();
        }

        let data = await postChange();

        if (data && data.success === false) {
          // Family-member duplicate caught server-side — redden the offending
          // block's fields and send the user back to the family screen.
          if (data.duplicate && typeof data.duplicate.index === 'number') {
            // duplicate.index counts only blocks with a first name — the same
            // set the backend validated — so filter blocks the same way.
            const namedBlocks = [...familyBox.querySelectorAll('.lvl-family-block')]
              .filter(b => (b.querySelector('[data-field="first_name"]')?.value || '').trim() !== '');
            const block = namedBlocks[data.duplicate.index];
            if (block) {
              const fields = data.duplicate.field === 'name_dob'
                ? ['first_name', 'last_name', 'dob']
                : [data.duplicate.field];
              fields.forEach(f => {
                const i = block.querySelector('[data-field="' + f + '"]');
                if (i) i.classList.add('lvl-invalid');
              });
            }
            showScreen('family');
            showError(familyError, data.message || 'A family member is already registered with ISGH.');
            if (block) block.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
          }
          showError(payError, data.message || 'Payment failed. Please try again.');
          return;
        }

        // 3DS — confirm the card, then finalize the EXISTING level change (no second charge).
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
          // 3DS passed — finalize the EXISTING level change via the dedicated endpoint.
          const finRes = await fetch(finalizeUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': lvlCsrf,
            },
            body: JSON.stringify({
              level_change_id: data.level_change_id,
              payment_intent_id: data.payment_intent_id,
            }),
          });
          const finData = await finRes.json();
          if (!finData || finData.success === false) {
            showError(payError, (finData && finData.message) || 'Level change could not be finalized.');
            return;
          }
          await goToSuccess(finData.level_change_id || data.level_change_id);
          return;
        }

        if (data && data.success) {
          await goToSuccess(data.level_change_id);
          return;
        }

        showError(payError, 'Unexpected response. Please try again.');
      } catch (err) {
        console.error('[Level] payment error:', err);
        showError(payError, 'Network error. Please try again.');
      } finally {
        payBtn.disabled = false;
        payBtn.textContent = originalLabel;
      }
    });

    // ── SCREEN 4: poll level-change status ───────────────────────────────
    async function goToSuccess(levelChangeId) {
      showScreen('success');
      successLabel.textContent = 'Processing your level change…';

      if (!levelChangeId) {
        successLabel.textContent = 'Level change received — finalizing…';
        return;
      }

      const statusUrl = statusUrlBase + '/' + levelChangeId;
      const maxAttempts = 8;

      for (let i = 0; i < maxAttempts; i++) {
        await new Promise(r => setTimeout(r, 1500));
        try {
          const res = await fetch(statusUrl, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': lvlCsrf },
          });
          const data = await res.json();
          if (data && data.processed === true) {
            // Prefer the human label from the picked card; to_type is a raw slug.
            const plan = ((_selected && _selected.label) || data.to_type || 'new');
            let msg = 'You are now on the ' + plan + ' plan.';
            if (data.wa_invoice_id) msg += ' Invoice Generated: INV-' + data.wa_invoice_id;
            successLabel.textContent = msg;
            return;
          }
          if (data && data.status === 'failed') {
            successLabel.textContent = 'Your payment was received, but we could not finalize the change automatically. Please contact ISGH support.';
            return;
          }
        } catch (err) {
          console.warn('[Level] status poll error:', err);
        }
      }
      // Timed out without `processed`
      successLabel.textContent = 'Level change received — finalizing…';
    }
  })();
</script>
