<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Choose Membership Type - ISGH</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    @font-face {
      font-family: "SF Pro regular";
      src: url("{{ asset('fonts/SFPRODISPLAYREGULAR.OTF') }}") format("woff2");
      font-weight: 400;
      font-style: normal;
    }
    @font-face {
      font-family: "SF Pro bold";
      src: url("{{ asset('fonts/SFPRODISPLAYBOLD.OTF') }}") format("woff2");
      font-weight: 700;
      font-style: normal;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green:       #0d7a55;
      --green-dark:  #085c40;
      --green-mid:   #10b981;
      --green-light: #e6f4ee;
      --bg:          #f8f8f8;
    }

    html, body {
      height: 100%;
      font-family: 'SF Pro regular', 'DM Sans', sans-serif;
      background: var(--bg);
    }

    /* ─── NAVBAR ──────────────────────────────────── */
        .nav{
        border: 10px solid #ffff;
      }
    .navbar-glass {
      background: rgba(10,10,10,0.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.08);
    }

    /* ─── HERO ───────────────────────────────────── */
    .hero-bg {
      background: linear-gradient(135deg, #31744aff 0%, #83968fff 100%);
      position: relative;
      overflow: hidden;
    }
    .hero-bg::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url('{{ asset("images/bussinesshandshake.png") }}');
      background-size: cover;
      background-position: center;
      mix-blend-mode: overlay;
      opacity: 0.9;
      z-index: 1;
    }

    /* ─── MAIN CONTAINER ──────────────────────────── */
    .main-container {
      max-width: 1200px;
      margin: -2rem auto 0;
      padding: 0 2rem 5rem;
      position: relative;
      z-index: 20;
    }

    /* ─── 3-COLUMN GRID ───────────────────────────── */
    /*
      With max-width 1200px, padding 2×2rem = 64px, 2×gap 2.5rem = 80px:
      available for columns = 1200 - 64 - 80 = 1056px
      side each = 1/3.8 × 1056 = ~278px  |  center = 1.8/3.8 × 1056 = ~500px
    */
    .membership-grid {
      display: grid;
      grid-template-columns: 1fr 1.8fr 1fr;
      gap: 2.5rem;
      align-items: start;
    }

    /* ─── SIDE COLUMNS ────────────────────────────── */
    .membership-card-side {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .side-item {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 260px;
    }
    .side-item img {
      width: 100%;
      max-width: 210px;
      height: 175px;
      object-fit: contain;
      margin-bottom: 0.9rem;
    }

    .side-item h3 {
      font-size: 0.9rem;
      color: #0d7a55;
      font-family: 'SF Pro bold';
      margin-bottom: 0.4rem;
    }

    .side-item p {
      font-size: 0.72rem;
      color: #6b7280;
      line-height: 1.55;
      padding: 0 0.25rem;
    }

    /* ─── CENTER CARD ─────────────────────────────── */
    .center-card {
      background: white;
      border-radius: 1.75rem;
      padding: 2.25rem 2.25rem 3rem;
      box-shadow: 0 4px 32px rgba(0,0,0,0.07), 0 1px 8px rgba(0,0,0,0.04);
      border: 1px solid #f1f5f9;
    }

    /* Step-1 indicator sits at the very top of the card */
    .step-indicator:first-child {
      margin-bottom: 1.25rem;
    }

    /* ─── MEMBERSHIP DROPDOWN ─────────────────────── */
    .membership-selector { margin-bottom: 1.5rem; text-align: left; position: relative; }
    .membership-selector .sel-label {
      position: absolute;
      top: -0.55rem;
      left: 0.95rem;
      background: white;
      padding: 0 0.35rem;
      font-size: 0.72rem;
      font-weight: 700;
      color: #374151;
      z-index: 5;
      font-family: 'SF Pro bold';
      letter-spacing: 0.01em;
    }
    .membership-selector select {
      width: 100%;
      padding: 0.88rem 2.5rem 0.88rem 1rem;
      border: 1.5px solid #e2e8f0;
      border-radius: 0.75rem;
      font-size: 0.88rem;
      color: #111827;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23374151' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      background-color: white;
      font-family: 'SF Pro regular', sans-serif;
      cursor: pointer;
    }
    .membership-selector select:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16,185,129,0.08);
    }

    /* ─── STEP INDICATOR ──────────────────────────── */
    .step-indicator {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      width: 100%;
      margin-bottom: 1.5rem;
    }

    .step-number {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'SF Pro bold';
      font-size: 1.1rem;
      background: white;
      border: 1.5px solid #e2e8f0;
      color: #94a3b8;
      margin: 0 auto 0.9rem;
      flex-shrink: 0;
    }

    .form-section-title {
      font-family: 'SF Pro bold';
      font-size: 1.4rem;
      color: #111827;
      margin-bottom: 0.35rem;
    }

    .step-subtitle {
      font-size: 0.82rem;
      color: #9ca3af;
    }

    /* ─── SCAN BUTTON ─────────────────────────────── */
    .scan-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      background: #10b981;
      color: white;
      padding: 0.48rem 1.2rem;
      border-radius: 999px;
      font-size: 0.72rem;
      font-family: 'SF Pro bold';
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
      margin: 0.5rem auto 1.5rem;
    }
    .scan-btn:hover { background: #059669; transform: translateY(-1px); }
    .scan-btn svg { width: 14px; height: 14px; flex-shrink: 0; }

    /* ─── MEMBERSHIP BANNER CARDS ─────────────────── */
    .membership-banner {
      border-radius: 0.85rem;
      padding: 1.25rem 1.35rem;
      color: white;
      position: relative;
      overflow: hidden;
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      min-height: 90px;
    }
    .membership-banner::after {
      content: '';
      position: absolute;
      top: -25px; right: -25px;
      width: 100px; height: 100px;
      border-radius: 50%;
      background: rgba(255,255,255,0.07);
      pointer-events: none;
    }
    .banner-left { flex: 1; min-width: 0; padding-right: 1rem; }
    .banner-title { font-family: 'SF Pro bold'; font-size: 0.95rem; margin-bottom: 0.2rem; }
    .banner-subtitle { font-size: 0.75rem; opacity: 0.85; margin-bottom: 0.6rem; }
    .banner-badges { display: flex; gap: 0.4rem; flex-wrap: wrap; }
    .banner-badge {
      background: rgba(255,255,255,0.18);
      border-radius: 999px;
      padding: 0.18rem 0.55rem;
      font-size: 0.65rem;
      font-family: 'SF Pro bold';
      white-space: nowrap;
    }
    .banner-price { text-align: right; flex-shrink: 0; }
    .banner-price .price-amount { font-family: 'SF Pro bold'; font-size: 1.75rem; line-height: 1; }
    .banner-price .price-period { font-size: 0.65rem; opacity: 0.75; margin-top: 0.15rem; }

    /* Banner color variants */
    /* Banner color variants - standardizing all to the checkomatic bronze/gold gradient */
    .banner-family,
    .banner-individual,
    .banner-flat,
    .banner-checkomatic,
    .banner-lifetime   { 
      background: linear-gradient(135deg, #8b4513 0%, #c0641e 100%); 
      box-shadow: 0 6px 20px rgba(139,69,19,0.22); 
    }

    /* ─── FORM FIELDS ─────────────────────────────── */
    .fields-stack { display: flex; flex-direction: column; gap: 1.25rem; }

    .field {
      position: relative;
      text-align: left;
    }
    .field label {
      position: absolute;
      top: -0.52rem;
      left: 0.9rem;
      background: #fff;
      padding: 0 0.3rem;
      font-size: 0.7rem;
      font-weight: 700;
      color: #374151;
      z-index: 5;
      font-family: 'SF Pro bold';
      letter-spacing: 0.01em;
      line-height: 1;
    }
    .field label span { color: #ef4444; }
    .field input, .field select {
      width: 100%;
      border: 1px solid #e2e8f0;
      border-radius: 0.65rem;
      padding: 0.85rem 2.5rem 0.85rem 0.95rem;
      font-size: 0.86rem;
      font-family: 'SF Pro regular', sans-serif;
      color: #111827;
      background: white;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .field select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.95rem center;
      background-color: white;
      cursor: pointer;
    }
    .field input:focus, .field select:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16,185,129,0.08);
    }
    .field input::placeholder { color: #c0c8d4; font-size: 0.83rem; }
    .field-icon {
      position: absolute;
      right: 0.9rem;
      top: 50%;
      transform: translateY(-50%);
      color: #c4c9d4;
      pointer-events: none;
      z-index: 3;
    }

    /* ─── MEMBER CARD (for flat membership) ────────── */
    .member-card {
      background: white;
      border: 1.5px solid #f0f0f0;
      border-radius: 1.1rem;
      padding: 1.4rem;
      margin-bottom: 1rem;
    }
    .member-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.4rem;
    }
    .member-tag {
      background: #fff4e5;
      color: #d97706;
      padding: 0.3rem 0.8rem;
      border-radius: 999px;
      font-size: 0.72rem;
      font-family: 'SF Pro bold';
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }
    .scan-btn-sm {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: #10b981;
      color: white;
      padding: 0.38rem 0.9rem;
      border-radius: 999px;
      font-size: 0.68rem;
      font-family: 'SF Pro bold';
      cursor: pointer;
      transition: 0.2s;
    }
    .scan-btn-sm:hover { background: #059669; }
    .scan-btn-sm svg { width: 12px; height: 12px; }

    .btn-add-member {
      width: 100%;
      padding: 0.8rem;
      border: 2px dashed #e5e7eb;
      border-radius: 0.9rem;
      color: #b0b8c8;
      font-family: 'SF Pro bold';
      font-size: 0.82rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      cursor: pointer;
      transition: 0.2s;
      margin-top: 1.25rem;
      background: none;
    }
    .btn-add-member:hover { border-color: #10b981; color: #10b981; background: #f0fdf4; }

    /* ─── ORDER SUMMARY ───────────────────────────── */
    .order-summary {
      background: linear-gradient(135deg, #0a5e3a, #0d7a52);
      border-radius: 0.9rem;
      padding: 1.2rem 1.3rem;
      color: white;
      margin-top: 1.5rem;
    }
    .order-summary-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.8rem;
    }
    .order-summary-title { font-family: 'SF Pro bold'; font-size: 0.86rem; }
    .order-badge {
      font-size: 0.62rem;
      color: rgba(255,255,255,0.6);
      font-style: italic;
      background: rgba(255,255,255,0.1);
      padding: 0.15rem 0.5rem;
      border-radius: 999px;
    }
    .order-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.75rem;
      color: rgba(255,255,255,0.75);
      padding-top: 0.6rem;
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    .order-row + .order-row { margin-top: 0.3rem; border-top: none; padding-top: 0; }
    .order-row span:last-child { font-family: 'SF Pro bold'; color: white; }
    .order-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 0.85rem;
      padding-top: 0.85rem;
      border-top: 1px solid rgba(255,255,255,0.15);
    }
    .order-total-label { font-family: 'SF Pro bold'; font-size: 0.86rem; color: white; }
    .order-total-amount { font-family: 'SF Pro bold'; font-size: 1.2rem; color: #fbbf24; }

    /* ─── CHECKBOXES ──────────────────────────────── */
    .checkbox-row {
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      margin: 1rem 0 0;
      text-align: left;
    }
    .checkbox-row input[type="checkbox"] {
      flex-shrink: 0;
      width: 1rem;
      height: 1rem;
      margin-top: 0.1rem;
      cursor: pointer;
      accent-color: #10b981;
    }
    .checkbox-row label {
      font-family: 'SF Pro regular';
      font-size: 0.78rem;
      color: #4b5563;
      cursor: pointer;
      line-height: 1.45;
    }

    .checkbox-box {
      background: #fdfdfd;
      border: 1px solid #eff0f2;
      border-radius: 0.55rem;
      padding: 0.7rem 0.85rem;
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      margin-bottom: 0.45rem;
      text-align: left;
    }
    .checkbox-box:hover { border-color: #10b981; background: #f6fdfb; }
    .checkbox-box label {
      font-size: 0.76rem;
      color: #4b5563;
      line-height: 1.45;
      cursor: pointer;
    }
    .custom-checkbox {
      width: 14px;
      height: 14px;
      border: 1px solid #d1d5db;
      border-radius: 3px;
      accent-color: #10b981;
      cursor: pointer;
      flex-shrink: 0;
      margin-top: 2px;
    }

    /* ─── SUBMIT BUTTON ───────────────────────────── */
    .btn-submit {
      width: 100%;
      margin-top: 1.5rem;
      padding: 0.88rem;
      background: #043d27;
      color: white;
      border: none;
      border-radius: 999px;
      font-size: 0.88rem;
      font-family: 'SF Pro bold';
      cursor: pointer;
      transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 0.5rem;
    }
    .btn-submit:hover {
      background: #033020;
      transform: translateY(-1px);
      box-shadow: 0 5px 16px rgba(4,61,39,0.2);
    }
    .btn-submit svg { width: 16px; height: 16px; flex-shrink: 0; }

    .secure-note {
      font-size: 0.66rem;
      color: #c4c9d4;
      text-align: center;
      margin-top: 0.6rem;
    }

    /* ─── LOCKED SECTION ──────────────────────────── */
    .locked-section {
      border: 2px dashed #d1fae5;
      border-radius: 1.5rem;
      padding: 2.25rem 1.75rem;
      text-align: center;
      background: #f6fdfb;
      margin-top: 1.5rem;
    }
    .lock-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 46px;
      height: 46px;
      background: white;
      border-radius: 50%;
      margin-bottom: 1.1rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }
    .lock-icon svg { width: 24px; height: 24px; color: #10b981; }
    .locked-section h3 { font-size: 1rem; font-weight: 700; color: #0d7a55; margin-bottom: 0.4rem; font-family: 'SF Pro bold'; }
    .locked-section p { font-size: 0.8rem; color: #0d7a55; line-height: 1.5; }
    .locked-steps { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1.5rem; }
    .locked-step {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: rgba(16,185,129,0.05);
      border-radius: 0.6rem;
      padding: 0.6rem 0.9rem;
      opacity: 0.45;
    }
    .locked-step-num {
      width: 22px; height: 22px;
      border-radius: 50%;
      background: #d1fae5;
      color: #0d7a55;
      font-size: 0.7rem;
      font-family: 'SF Pro bold';
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .locked-step-bar { height: 7px; flex: 1; background: #d1fae5; border-radius: 999px; }

    /* ─── SECTION DIVIDER ─────────────────────────── */
    .section-divider { height: 1px; background: #f1f3f5; margin: 1.75rem 0; }

    /* ─── FLAT PAY CHECK ──────────────────────────── */
    .flat-pay-note {
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      font-size: 0.75rem;
      color: #10b981;
      font-family: 'SF Pro bold';
      background: #f0fdf4;
      border: 1px solid #d1fae5;
      border-radius: 0.65rem;
      padding: 0.75rem 1rem;
      margin: 0.5rem 0 1.5rem;
      text-align: left;
    }

    /* ─── TERMS CHECKBOXES — plain row style ─────── */
    /* Override the bordered checkbox-box in terms sections to plain rows */
    .terms-list .checkbox-box {
      background: transparent;
      border: none;
      border-radius: 0;
      padding: 0.45rem 0;
      margin-bottom: 0.25rem;
      gap: 0.65rem;
    }
    .terms-list .checkbox-box:hover {
      background: transparent;
      border: none;
    }
    .terms-list .checkbox-box label {
      font-size: 0.78rem;
      color: #4b5563;
    }

    /* ─── RESPONSIVE ──────────────────────────────── */
    @media (max-width: 960px) {
      .membership-grid {
        grid-template-columns: 1fr;
      }
      .membership-card-side {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1.5rem;
        padding-top: 1rem;
      }
      .side-item {
        width: calc(50% - 0.75rem);
        margin-bottom: 0.5rem;
      }
      .side-item img { max-width: 120px; height: 120px; }
    }

    @media (max-width: 900px) {
      .membership-grid { grid-template-columns: 1fr; }
      .membership-card-side { flex-direction: row; flex-wrap: wrap; justify-content: center; gap: 1.5rem; }
      .side-item { flex: 0 0 calc(50% - 1rem); margin-bottom: 0; }
      .side-item img { height: 130px; }
    }

    @media (max-width: 560px) {
      .center-card { padding: 1.75rem 1.25rem 2rem; }
      .banner-price .price-amount { font-size: 1.3rem; }
    }

    @media (max-width: 500px) {
      .side-item { flex: 0 0 100%; }
      .main-container { padding: 0 1rem 4rem; }
      .center-card { padding: 1.5rem 1rem 2rem; }
    }

    .order-row { flex-wrap: wrap; }

    /* ─── SPOUSE / MEMBER BLOCKS ───────────────────── */
    .spouse-block {
      border: 1px solid #e5e7eb;
      border-radius: 0.85rem;
      padding: 1rem 1.1rem 0.5rem;
      margin-bottom: 1rem;
      background: #fafafa;
    }
    .spouse-block-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.75rem;
    }
    .btn-remove-block {
      background: none;
      border: none;
      color: #ef4444;
      font-size: 0.78rem;
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      padding: 0.2rem 0.5rem;
      border-radius: 0.4rem;
      transition: background 0.15s;
    }
    .btn-remove-block:hover { background: #fee2e2; }

    /* Submission overlay */
    .submit-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.55);
      display: flex; align-items: center; justify-content: center;
      z-index: 9999; display: none; backdrop-filter: blur(3px);
    }
    .submit-overlay.visible { display: flex; }
    .submit-spinner {
      background: white; border-radius: 1.5rem; padding: 2.5rem 3rem;
      text-align: center; box-shadow: 0 24px 64px rgba(0,0,0,0.25);
      min-width: 300px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .spin-icon {
      width: 52px; height: 52px; border: 3px solid #e5e7eb;
      border-top-color: var(--green); border-radius: 50%;
      animation: spin 0.9s linear infinite; margin: 0 auto;
    }
    .overlay-step {
      margin-top: 1.1rem; font-family: 'DM Sans', sans-serif;
      font-size: 0.92rem; color: #111827; font-weight: 600;
    }
    .overlay-sub {
      margin-top: 0.3rem; font-family: 'DM Sans', sans-serif;
      font-size: 0.78rem; color: #9ca3af;
    }
    .overlay-timer {
      display: inline-block; margin-top: 1rem;
      background: #f3f4f6; border-radius: 999px;
      padding: 0.3rem 1rem; font-family: 'SF Pro bold', monospace;
      font-size: 0.85rem; color: #6b7280; letter-spacing: 0.04em;
    }
    .overlay-steps-track {
      display: flex; align-items: center; justify-content: center;
      gap: 0.4rem; margin-top: 1.2rem;
    }
    .overlay-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: #e5e7eb; transition: background 0.3s;
    }
    .overlay-dot.active { background: var(--green); }
    .overlay-dot.done   { background: #10b981; }

    /* ZIP / phone validation feedback */
    .zip-msg, .phone-msg { font-size: 12px; margin-top: 4px; min-height: 16px; }
    .zip-msg.error,   .phone-msg.error   { color: #dc2626; }
    .zip-msg.success, .phone-msg.success { color: #10b981; }
    .zone-field { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 12px; }
    .zone-field label { color: #166534; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
    .zone-field select { width: 100%; margin-top: 6px; padding: 7px 10px; border: 1px solid #86efac; border-radius: 6px; background: #fff; font-size: 14px; }
    .zone-field .zone-text { font-size: 14px; font-weight: 600; color: #15803d; margin-top: 6px; padding: 6px 10px; background: #dcfce7; border-radius: 6px; display: inline-block; }
  </style>

  <script>
    // ─── SECTION TOGGLE ──────────────────────────────────────────────────────
    function toggleMembershipForm() {
      const val = document.getElementById("membershipSelector").value;
      const sections = [
        "defaultLockedSection","familyMembershipSection","individualMembershipSection",
        "flatMembershipSection","checkomaticFamilySection","checkomaticIndividualSection",
        "lifetimeFamilySection","lifetimeIndividualSection"
      ];
      sections.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = "none"; });

      const sectionMap = {
        "family":                 "familyMembershipSection",
        "individual":             "individualMembershipSection",
        "flat":                   "flatMembershipSection",
        "checkomatic_family":     "checkomaticFamilySection",
        "checkomatic_individual": "checkomaticIndividualSection",
        "lifetime_family":        "lifetimeFamilySection",
        "lifetime_individual":    "lifetimeIndividualSection",
      };
      const target = sectionMap[val] || "defaultLockedSection";
      const targetEl = document.getElementById(target);
      targetEl.style.display = "block";
      attachFieldFeedback(targetEl);
      // Auto-fill spouse address fields from primary for family membership types
      const familyPrefixes = { family: 'fam', checkomatic_family: 'ckf', lifetime_family: 'ltf' };
      if (familyPrefixes[val]) autoFillSpouseAddresses(familyPrefixes[val]);
    }

    // ─── DYNAMIC SPOUSE BLOCKS ───────────────────────────────────────────────
    const spouseCounts = { fam: 1, ckf: 1, ltf: 1 };

    function addSpouseBlock(prefix) {
      const container = document.getElementById(prefix + '_spouses_container');
      if (!container) return;
      const idx = spouseCounts[prefix]++;
      const block = document.createElement('div');
      block.className = 'spouse-block';
      block.id = prefix + '_spouse_block_' + idx;
      block.innerHTML = `
        <div class="spouse-block-header">
          <div class="member-tag">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Spouse ${idx + 1}
          </div>
          <button type="button" class="btn-remove-block" onclick="removeBlock('${prefix}_spouse_block_${idx}')">✕ Remove</button>
        </div>
        <div class="fields-stack">
         <div class="field"><label>Email Address</label><input type="email" id="${prefix}_spouse_${idx}_email" placeholder="spouse@example.com"></div>
          <div class="field"><label>First Name <span>*</span></label><input type="text" placeholder="First Name"></div>
          <div class="field"><label>Middle Name</label><input type="text" id="${prefix}_spouse_${idx}_middle_name" placeholder="Middle Name (Optional)"></div>
          <div class="field"><label>Last Name <span>*</span></label><input type="text" placeholder="Last Name"></div>
          <div class="field"><label>Date of Birth <span>*</span></label><input type="text" placeholder="MM/DD/YYYY"></div>
          <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="${prefix}_spouse_${idx}_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="${prefix}_spouse_${idx}_phone_msg" class="phone-msg"></div></div>
          <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" id="${prefix}_spouse_${idx}_txdl" placeholder="e.g. TX7234578"></div>
          <div class="field"><label>Gender</label><select id="${prefix}_spouse_${idx}_gender"><option value="">Select Gender</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
          <div class="field"><label>Street Address</label><input type="text" id="${prefix}_spouse_${idx}_street" placeholder="Auto-filled from primary"></div>
          <div class="field"><label>City</label><input type="text" id="${prefix}_spouse_${idx}_city" placeholder="Auto-filled from primary"></div>
          <div class="field"><label>ZIP Code</label><input type="text" id="${prefix}_spouse_${idx}_zip" placeholder="Auto-filled from primary"></div>
          <div class="field"><label>State</label><select id="${prefix}_spouse_${idx}_state"><option selected>Texas</option></select></div>
        </div>`;
      autoFillSpouseAddress(prefix, idx);
      attachFieldFeedback(block);
      const addBtn = container.nextElementSibling;
      container.parentNode.insertBefore(block, addBtn);
    }

    function removeBlock(blockId) {
      const block = document.getElementById(blockId);
      if (block) block.remove();
    }

    // ─── DYNAMIC FLAT MEMBER BLOCKS ──────────────────────────────────────────
    const FLAT_FEE_PER_MEMBER = 20;
    let flatMemberCount = 1;

    function updateFlatTotal() {
      const container = document.getElementById('flat_members_container');
      if (!container) return;
      // 1 primary member (flt_ form) + additional family member cards
      const count = 1 + container.querySelectorAll('.member-card').length;
      const total = count * FLAT_FEE_PER_MEMBER;
      const fmt = '$' + total.toFixed(2);
      const feeEl   = document.getElementById('flat_fee_display');
      const totalEl = document.getElementById('flat_total_display');
      const countEl = document.getElementById('flat_member_count');
      const btnEl   = document.getElementById('flt_submit_btn');
      if (feeEl)   feeEl.textContent   = fmt;
      if (totalEl) totalEl.textContent = fmt;
      if (countEl) countEl.textContent = count + ' member' + (count !== 1 ? 's' : '') + ' × $' + FLAT_FEE_PER_MEMBER;
      if (btnEl)   btnEl.innerHTML     = btnEl.innerHTML.replace(/Pay \$[\d,]+\.\d{2}/, 'Pay ' + fmt);
    }

    function addFlatMemberBlock() {
      const container = document.getElementById('flat_members_container');
      if (!container) return;
      const idx        = flatMemberCount++;
      const displayNum = container.querySelectorAll('.member-card').length + 1;
      const block = document.createElement('div');
      block.className = 'member-card';
      block.id = 'flat_member_block_' + idx;
      block.innerHTML = `
        <div class="member-header">
          <div class="member-tag">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Member ${displayNum}
          </div>
          <button type="button" class="btn-remove-block" onclick="removeFlatBlock('flat_member_block_${idx}')">✕ Remove</button>
        </div>
        <div class="fields-stack">
        <div class="field"><label>Email Address <span>*</span></label><input type="email" id="flat_member_${idx}_email" placeholder="member@example.com"></div>
          <div class="field"><label>First Name <span>*</span></label><input type="text" id="flat_member_${idx}_first_name" placeholder="Ahmad"></div>
          <div class="field"><label>Middle Name</label><input type="text" id="flat_member_${idx}_middle_name" placeholder="Middle Name (Optional)"></div>
          <div class="field"><label>Last Name <span>*</span></label><input type="text" id="flat_member_${idx}_last_name" placeholder="Ali"></div>
          <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="flat_member_${idx}_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="flat_member_${idx}_phone_msg" class="phone-msg"></div></div>
          <div class="field"><label>Date of Birth <span>*</span></label><input type="text" id="flat_member_${idx}_dob" placeholder="MM/DD/YYYY"></div>
          <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" id="flat_member_${idx}_txdl" placeholder="e.g. TX7234578"></div>
          
          <div class="field"><label>Relation <span>*</span></label><select id="flat_member_${idx}_relation"><option value="">Select Relation</option><option>Child</option><option>Sibling</option><option>Parent</option><option>Spouse</option></select></div>
          <div class="field"><label>Street Address</label><input type="text" id="flat_member_${idx}_street" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
          <div class="field"><label>City</label><input type="text" id="flat_member_${idx}_city" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
          <div class="field"><label>ZIP Code</label><input type="text" id="flat_member_${idx}_zip" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
          <div class="field"><label>State</label><input type="text" id="flat_member_${idx}_state" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
        </div>`;
      container.appendChild(block);
      autoFillFlatMemberAddress(idx);
      attachFieldFeedback(block);
      updateFlatTotal();
    }

    function removeFlatBlock(blockId) {
      const el = document.getElementById(blockId);
      if (el) el.remove();
      // Renumber remaining member cards so labels stay sequential
      const container = document.getElementById('flat_members_container');
      if (container) {
        container.querySelectorAll('.member-card').forEach((card, i) => {
          const tag = card.querySelector('.member-tag');
          if (tag) {
            const svg = tag.querySelector('svg')?.outerHTML || '';
            tag.innerHTML = svg + '\n            Member ' + (i + 1);
          }
        });
      }
      updateFlatTotal();
    }

    // ─── DATA COLLECTION ─────────────────────────────────────────────────────
    function gv(id) { return (document.getElementById(id)?.value || '').trim(); }

    const SECTION_IDS = {
      family: 'familyMembershipSection', individual: 'individualMembershipSection',
      flat: 'flatMembershipSection', checkomatic_family: 'checkomaticFamilySection',
      checkomatic_individual: 'checkomaticIndividualSection',
      lifetime_family: 'lifetimeFamilySection', lifetime_individual: 'lifetimeIndividualSection',
    };

    function collectPrimary(prefix) {
      const section = document.getElementById(SECTION_IDS[prefixToType(prefix)]);
      const stack   = section?.querySelectorAll('.fields-stack')[0];
      // Collect only plain input fields (not hidden, not inside zone-field, not already collected by ID)
      const inputs  = stack ? [...stack.querySelectorAll('input:not([type="hidden"]):not(.zone-field *):not([id])')] : [];
      const selects = stack ? [...stack.querySelectorAll('select:not(.zone-field *)')] : [];
      const byPos   = ['email','first_name','last_name','dob','tx_dl','street','city'];
      const primary = {};
      byPos.forEach((f, i) => { primary[f] = (inputs[i]?.value || '').trim(); });
      // Use IDs for zip and state so zone dropdown can't interfere
      primary.middle_name = gv(prefix + '_middle_name');
      primary.zip   = (document.getElementById(prefix + '_zip')?.value   || '').trim();
      primary.state = (document.getElementById(prefix + '_state')?.value  || '').trim();
      primary.phone = gv(prefix + '_phone');
      // Some sections (e.g. flat) have explicit IDs for street/city for auto-fill;
      // override positional values with ID-based ones when the element exists.
      const streetEl = document.getElementById(prefix + '_street');
      const cityEl   = document.getElementById(prefix + '_city');
      if (streetEl) primary.street = streetEl.value.trim();
      if (cityEl)   primary.city   = cityEl.value.trim();
      return primary;
    }

    function prefixToType(prefix) {
      return { fam:'family', ind:'individual', flt:'flat', ckf:'checkomatic_family',
               cki:'checkomatic_individual', ltf:'lifetime_family', lti:'lifetime_individual' }[prefix];
    }

    function collectSpouses(prefix) {
      const container = document.getElementById(prefix + '_spouses_container');
      if (!container) return [];
      const blocks = container.querySelectorAll('.spouse-block');
      const spouses = [];
      blocks.forEach(block => {
        // Exclude inputs already collected by ID so positional mapping stays correct
        const inputs = [...block.querySelectorAll('input:not([type="checkbox"]):not([id])')];
        if (!inputs[0]?.value?.trim()) return;
        const idxMatch = block.id.match(/_(\d+)$/);
        const idx = idxMatch ? idxMatch[1] : '0';
        const ip = prefix + '_spouse_' + idx + '_';
        spouses.push({
          first_name:  (inputs[0]?.value||'').trim(),
          last_name:   (inputs[1]?.value||'').trim(),
          middle_name: (document.getElementById(ip + 'middle_name')?.value||'').trim(),
          dob:         (inputs[2]?.value||'').trim(),
          phone:       (document.getElementById(ip + 'phone')?.value||'').trim(),
          tx_dl:       (document.getElementById(ip + 'txdl')?.value||'').trim(),
          email:       (document.getElementById(ip + 'email')?.value||'').trim(),
          gender:      (document.getElementById(ip + 'gender')?.value||'').trim(),
          street:      (document.getElementById(ip + 'street')?.value||'').trim(),
          city:        (document.getElementById(ip + 'city')?.value||'').trim(),
          zip:         (document.getElementById(ip + 'zip')?.value||'').trim(),
          state:       (document.getElementById(ip + 'state')?.value||'').trim(),
        });
      });
      return spouses;
    }

    // ─── SPOUSE ADDRESS AUTO-FILL ─────────────────────────────────────────────
    function autoFillSpouseAddress(prefix, idx) {
      const street = document.getElementById(prefix + '_street')?.value || '';
      const city   = document.getElementById(prefix + '_city')?.value   || '';
      const zip    = document.getElementById(prefix + '_zip')?.value    || '';
      const state  = document.getElementById(prefix + '_state')?.value  || '';
      const ip = prefix + '_spouse_' + idx + '_';
      const sStreet = document.getElementById(ip + 'street');
      const sCity   = document.getElementById(ip + 'city');
      const sZip    = document.getElementById(ip + 'zip');
      const sState  = document.getElementById(ip + 'state');
      if (sStreet) sStreet.value = street;
      if (sCity)   sCity.value   = city;
      if (sZip)    sZip.value    = zip;
      if (sState)  sState.value  = state;
    }

    function autoFillSpouseAddresses(prefix) {
      const container = document.getElementById(prefix + '_spouses_container');
      if (!container) return;
      container.querySelectorAll('.spouse-block').forEach(block => {
        const idxMatch = block.id.match(/_(\d+)$/);
        if (idxMatch) autoFillSpouseAddress(prefix, idxMatch[1]);
      });
    }

    function collectFlatMembers() {
      const container = document.getElementById('flat_members_container');
      if (!container) return [];
      const members = [];
      container.querySelectorAll('.member-card').forEach(block => {
        const idxMatch = block.id.match(/_(\d+)$/);
        const midx = idxMatch ? idxMatch[1] : '0';
        const firstName = gv('flat_member_' + midx + '_first_name');
        if (!firstName) return; // skip empty blocks
        members.push({
          first_name:  firstName,
          middle_name: gv('flat_member_' + midx + '_middle_name'),
          last_name:   gv('flat_member_' + midx + '_last_name'),
          phone:       gv('flat_member_' + midx + '_phone'),
          dob:         gv('flat_member_' + midx + '_dob'),
          tx_dl:       gv('flat_member_' + midx + '_txdl'),
          email:       gv('flat_member_' + midx + '_email'),
          relation:    gv('flat_member_' + midx + '_relation') || 'Family Member',
          street:      gv('flat_member_' + midx + '_street'),
          city:        gv('flat_member_' + midx + '_city'),
          zip:         gv('flat_member_' + midx + '_zip'),
          state:       gv('flat_member_' + midx + '_state'),
        });
      });
      return members;
    }

    // ─── FLAT MEMBER ADDRESS AUTO-FILL ───────────────────────────────────────
    function autoFillFlatMemberAddress(idx) {
      const street = document.getElementById('flt_street')?.value || '';
      const city   = document.getElementById('flt_city')?.value   || '';
      const zip    = document.getElementById('flt_zip')?.value    || '';
      const state  = document.getElementById('flt_state')?.value  || '';
      const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
      set('flat_member_' + idx + '_street', street);
      set('flat_member_' + idx + '_city',   city);
      set('flat_member_' + idx + '_zip',    zip);
      set('flat_member_' + idx + '_state',  state);
    }

    function autoFillFlatMemberAddresses() {
      const container = document.getElementById('flat_members_container');
      if (!container) return;
      container.querySelectorAll('.member-card').forEach(block => {
        const m = block.id.match(/_(\d+)$/);
        if (m) autoFillFlatMemberAddress(m[1]);
      });
    }

    function collectTerms(sectionId) {
      const section = document.getElementById(sectionId);
      const cb      = section?.querySelector('.terms-list input[type="checkbox"]');
      const agreed  = cb?.checked ?? false;
      return { agree: agreed, responsibility: agreed, privacy: agreed, communications: agreed };
    }

    // ─── SUBMISSION ──────────────────────────────────────────────────────────
    // ─── TIMER ───────────────────────────────────────────────────────────────
    let _timerStart = 0, _timerInterval = null;

    function startTimer() {
      _timerStart = performance.now();
      const el = document.getElementById('overlayTimer');
      _timerInterval = setInterval(() => {
        const s = ((performance.now() - _timerStart) / 1000).toFixed(1);
        if (el) el.textContent = s + 's';
      }, 100);
    }

    function stopTimer() {
      clearInterval(_timerInterval);
      const elapsed = ((performance.now() - _timerStart) / 1000).toFixed(1);
      const el = document.getElementById('overlayTimer');
      if (el) el.textContent = elapsed + 's';
      return elapsed;
    }

    function setOverlayStep(step, text, sub) {
      const stepEl = document.getElementById('overlayStep');
      const subEl  = document.getElementById('overlaySub');
      if (stepEl) stepEl.textContent = text;
      if (subEl)  subEl.textContent  = sub || 'Please do not close this window.';
      [1,2].forEach(n => {
        const dot = document.getElementById('odot' + n);
        if (!dot) return;
        dot.className = 'overlay-dot' + (n < step ? ' done' : n === step ? ' active' : '');
      });
    }

    // ─── US PHONE VALIDATION ─────────────────────────────────────────────────
    // Accepts: (832) 555-0100 | 832-555-0100 | 8325550100 | +1 832 555 0100
    // Rejects: any non-US format (fewer/more than 10 digits, or wrong country code)
    // ─── DOB FORMAT & VALIDATION ─────────────────────────────────────────────
    function formatDob(input) {
      const cursor = input.selectionStart;
      const prev   = input.value;
      let v = prev.replace(/\D/g, '');
      if (v.length > 8) v = v.slice(0, 8);
      let out = v;
      if (v.length > 4)      out = v.slice(0,2) + '/' + v.slice(2,4) + '/' + v.slice(4);
      else if (v.length > 2) out = v.slice(0,2) + '/' + v.slice(2);
      input.value = out;
      // keep cursor roughly in the right place
      const added = out.length - prev.length;
      try { input.setSelectionRange(cursor + added, cursor + added); } catch(_) {}
    }

    function validateDob(input) {
      const val = input.value.trim();
      if (!val) { input.style.borderColor = ''; return true; }
      const m = val.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
      if (!m) { input.style.borderColor = '#dc2626'; return false; }
      const month = parseInt(m[1], 10), day = parseInt(m[2], 10), year = parseInt(m[3], 10);
      const date  = new Date(year, month - 1, day);
      const valid = date.getFullYear() === year
                 && date.getMonth()    === month - 1
                 && date.getDate()     === day
                 && date < new Date();
      input.style.borderColor = valid ? '#10b981' : '#dc2626';
      return valid;
    }

    // ─── REQUIRED-FIELD HIGHLIGHT (replaces alert) ────────────────────────────
    function highlightMissingFields(sectionEl) {
      let firstBad = null;
      sectionEl.querySelectorAll('.field').forEach(field => {
        if (field.closest('.zone-field')) return;
        const label    = field.querySelector('label');
        const required = label && label.querySelector('span');
        if (!required) return;
        const inp = field.querySelector('input:not([readonly]):not([type="checkbox"]), select');
        if (!inp) return;
        const empty = !inp.value.trim();
        if (empty) {
          inp.style.borderColor = '#dc2626';
          if (!firstBad) firstBad = inp;
        } else {
          inp.style.borderColor = '#10b981';
        }
      });
      if (firstBad) {
        firstBad.focus();
        firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
      }
      return true;
    }

    // ─── REAL-TIME GREEN/RED FEEDBACK ON REQUIRED FIELDS ─────────────────────
    function attachFieldFeedback(sectionEl) {
      sectionEl.querySelectorAll('.field').forEach(field => {
        if (field.closest('.zone-field')) return;
        const label    = field.querySelector('label');
        const required = label && label.querySelector('span');
        if (!required) return;
        const inp = field.querySelector('input:not([readonly]):not([type="checkbox"]), select');
        if (!inp || inp._feedbackAttached) return;
        inp._feedbackAttached = true;
        const update = () => {
          if (!inp.value.trim()) {
            inp.style.borderColor = '#dc2626';
          } else {
            inp.style.borderColor = '#10b981';
          }
        };
        inp.addEventListener('input', update);
        inp.addEventListener('change', update);
        inp.addEventListener('blur', update);
      });
    }

    function validateUsPhone(input) {
      const raw    = input.value.trim();
      const msgId  = input.id + '_msg';
      const msgEl  = document.getElementById(msgId);
      if (!raw) { if (msgEl) { msgEl.textContent = ''; msgEl.className = 'phone-msg'; } return true; }

      // Strip everything except digits and leading +
      let digits = raw.replace(/[^\d+]/g, '');
      // Handle +1 country code
      if (digits.startsWith('+1')) digits = digits.slice(2);
      else if (digits.startsWith('1') && digits.length === 11) digits = digits.slice(1);

      const valid = /^\d{10}$/.test(digits);
      if (msgEl) {
        msgEl.textContent = valid
          ? '✓ Valid US phone number'
          : 'Please enter a valid US phone number. Examples: (832) 555-0100 · +1 713-555-0199 · 2815550100';
        msgEl.className = 'phone-msg ' + (valid ? 'success' : 'error');
      }
      if (!valid) input.style.borderColor = '#dc2626';
      else         input.style.borderColor = '#10b981';
      return valid;
    }

    // ─── TERMS MODAL ─────────────────────────────────────────────────────────
    let _termsSourceCheckbox = null;

    function openTermsModal(event) {
      event.preventDefault();
      // Find the nearest checkbox in the same checkbox-box wrapper
      const box = event.target.closest('.checkbox-box');
      _termsSourceCheckbox = box?.querySelector('input[type="checkbox"]') ?? null;
      const modal = document.getElementById('termsModal');
      if (modal) { modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }

    function closeTermsModal() {
      const modal = document.getElementById('termsModal');
      if (modal) { modal.style.display = 'none'; document.body.style.overflow = ''; }
      _termsSourceCheckbox = null;
    }

    function acceptTermsAndClose() {
      if (_termsSourceCheckbox) _termsSourceCheckbox.checked = true;
      closeTermsModal();
    }

    // Close on backdrop click
    // ─── DOB EVENT DELEGATION (covers static + dynamic fields) ───────────────
    document.addEventListener('input', function(e) {
      if (e.target.tagName === 'INPUT' && e.target.placeholder === 'MM/DD/YYYY') {
        formatDob(e.target);
      }
    });
    document.addEventListener('blur', function(e) {
      if (e.target.tagName === 'INPUT' && e.target.placeholder === 'MM/DD/YYYY') {
        validateDob(e.target);
      }
    }, true);

    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('termsModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeTermsModal();
      });
      updateFlatTotal(); // initialise flat fee display on load
    });

    // ─── ZIP VALIDATION ──────────────────────────────────────────────────────
    const _zipState = {}; // prefix → { valid: bool, zone: string }

    async function validateZip(input, prefix) {
      const zip   = input.value.trim();
      const msgEl = document.getElementById(prefix + '_zip_msg');
      const cfEl  = document.getElementById(prefix + '_center_field');
      const cdEl  = document.getElementById(prefix + '_center_display');

      _zipState[prefix] = { valid: false, zone: '' };
      if (msgEl) { msgEl.textContent = ''; msgEl.className = 'zip-msg'; }
      if (cfEl)  cfEl.style.display = 'none';

      if (!zip || zip.replace(/\D/g,'').length < 5) return;

      try {
        const res  = await fetch('/membership/zip-lookup', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
          body:    JSON.stringify({ zip }),
        });
        const data = await res.json();

        if (!data.success) {
          if (msgEl) { msgEl.textContent = data.message; msgEl.className = 'zip-msg error'; }
          return;
        }

        const centers = data.centers;
        _zipState[prefix] = { valid: true, zone: '' };

        if (centers.length === 1) {
          _zipState[prefix].zone = centers[0];
          if (cdEl)  cdEl.innerHTML = `<span class="zone-text">📍 ${centers[0]}</span>`;
          if (cfEl)  cfEl.style.display = '';
          if (msgEl) { msgEl.textContent = '✓ Center assigned'; msgEl.className = 'zip-msg success'; }
        } else {
          const opts = centers.map(c => `<option value="${c}">${c}</option>`).join('');
          if (cdEl)  cdEl.innerHTML = `<select onchange="_zipState['${prefix}'].zone=this.value"><option value="">Select your center…</option>${opts}</select>`;
          if (cfEl)  cfEl.style.display = '';
          if (msgEl) { msgEl.textContent = '✓ Multiple centers found — please select one'; msgEl.className = 'zip-msg success'; }
        }
      } catch (e) {
        if (msgEl) { msgEl.textContent = 'ZIP lookup failed. Please try again.'; msgEl.className = 'zip-msg error'; }
      }
    }

    // ─── SUBMISSION ──────────────────────────────────────────────────────────
    async function submitMembership(type) {
      const prefixMap = {
        family:'fam', individual:'ind', flat:'flt', checkomatic_family:'ckf',
        checkomatic_individual:'cki', lifetime_family:'ltf', lifetime_individual:'lti',
      };
      const prefix      = prefixMap[type];
      const sectionId   = SECTION_IDS[type];
      const primary     = collectPrimary(prefix);
      const isFamily    = ['family','checkomatic_family','lifetime_family'].includes(type);
      const spouses     = isFamily ? collectSpouses(prefix) : [];
      const flatMembers = type === 'flat' ? collectFlatMembers() : [];
      const terms       = collectTerms(sectionId);

      const sectionEl = document.getElementById(sectionId);

      // Highlight all empty required fields first
      if (sectionEl && !highlightMissingFields(sectionEl)) return;

      if (!primary.first_name || !primary.last_name || !primary.email) {
        alert('Please fill in your First Name, Last Name, and Email Address.');
        return;
      }

      // Phone validation — primary + all spouse/member phone inputs in this section
      const allPhoneInputs = sectionEl
        ? [...sectionEl.querySelectorAll('input[id$="_phone"]')]
        : [document.getElementById(prefix + '_phone')].filter(Boolean);
      for (const phoneInput of allPhoneInputs) {
        if (phoneInput.value.trim() && !validateUsPhone(phoneInput)) {
          phoneInput.focus();
          phoneInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return;
        }
      }
      // Block if primary phone is empty
      const primaryPhone = document.getElementById(prefix + '_phone');
      if (primaryPhone && !primaryPhone.value.trim()) {
        primaryPhone.style.borderColor = '#dc2626';
        primaryPhone.focus();
        primaryPhone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      // DOB validation — primary + all spouse/member DOB fields in this section
      if (sectionEl) {
        const allDobInputs = [...sectionEl.querySelectorAll('input[placeholder="MM/DD/YYYY"]')];
        for (const dobInput of allDobInputs) {
          if (!validateDob(dobInput)) {
            dobInput.focus();
            dobInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
          }
        }
      }

      // ZIP / center validation
      if (!_zipState[prefix]?.valid) {
        const zipInput = document.getElementById(prefix + '_zip');
        if (zipInput) { zipInput.style.borderColor = '#dc2626'; zipInput.focus(); zipInput.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        return;
      }
      const zone = _zipState[prefix]?.zone || '';
      if (!zone) {
        const cfEl = document.getElementById(prefix + '_center_field');
        if (cfEl) cfEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      const overlay   = document.getElementById('submitOverlay');
      const btn       = document.getElementById(prefix + '_submit_btn');

      if (overlay) overlay.classList.add('visible');
      if (btn) { btn.disabled = true; btn.textContent = 'Processing…'; }
      startTimer();
      setOverlayStep(1, 'Saving your registration…', 'Securely sending your information.');

      try {
        // Step 1 — save form data + create Stripe Checkout Session
        const res  = await fetch('/membership/checkout', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
          body: JSON.stringify({ membership_type: type, primary, spouses, flat_members: flatMembers, terms, zone }),
        });
        const data = await res.json();

        if (!data.success) {
          const msg = data.message || Object.values(data.errors ?? {}).flat().join('\n');
          throw new Error(msg);
        }

        // Step 2 — redirect to Stripe Checkout
        setOverlayStep(2, 'Redirecting to Stripe…', 'You will be taken to the secure Stripe payment page.');
        stopTimer();
        window.location.href = data.checkout_url;

      } catch (err) {
        stopTimer();
        if (overlay) overlay.classList.remove('visible');
        if (btn) { btn.disabled = false; btn.textContent = 'Complete Registration'; }
        alert('Error: ' + err.message);
      }
    }
  </script>
</head>

<body>

<!-- Processing overlay -->
<div id="submitOverlay" class="submit-overlay">
  <div class="submit-spinner">
    <div class="spin-icon"></div>
    <div class="overlay-step" id="overlayStep">Saving your registration…</div>
    <div class="overlay-sub" id="overlaySub">Securely sending your information.</div>
    <div class="overlay-timer" id="overlayTimer">0.0s</div>
    <div class="overlay-steps-track">
      <div class="overlay-dot active" id="odot1"></div>
      <div class="overlay-dot" id="odot2"></div>
    </div>
  </div>
</div>

<!-- Terms & Conditions Modal -->
<div id="termsModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;max-width:560px;width:92%;padding:2rem 2rem 1.5rem;box-shadow:0 24px 64px rgba(0,0,0,0.25);position:relative;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.25rem;">
      <div style="width:36px;height:36px;border-radius:50%;background:#1a4a2e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><path d="M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z"/></svg>
      </div>
      <h2 style="font-size:1.2rem;font-weight:700;color:#1a4a2e;margin:0;">ISGH Membership — Terms &amp; Conditions</h2>
      <button onclick="closeTermsModal()" style="margin-left:auto;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#6b7280;line-height:1;">&times;</button>
    </div>
    <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:1.5rem;">
      <div style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
        <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;margin-top:-1px;">1.</span>
        <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">I agree to the <strong>Terms and Conditions</strong> set by the Islamic Society of Greater Houston and acknowledge that membership is subject to ISGH policies.</p>
      </div>
      <div style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
        <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;margin-top:-1px;">2.</span>
        <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">I <strong>assume responsibility</strong> for the accuracy of any information recorded in my membership profile and agree to keep it up to date.</p>
      </div>
      <div style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
        <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;margin-top:-1px;">3.</span>
        <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">I agree that ISGH follows a strict <strong>Privacy Policy</strong> and my personal information will be kept confidential and not shared without consent.</p>
      </div>
      <div style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
        <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;margin-top:-1px;">4.</span>
        <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">I agree to receive <strong>communications</strong> from ISGH including membership updates, event announcements, and newsletters as specified in the ISGH Privacy Policy.</p>
      </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button onclick="closeTermsModal()" style="padding:10px 20px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#6b7280;font-size:14px;cursor:pointer;">Cancel</button>
      <button onclick="acceptTermsAndClose()" style="padding:10px 24px;border:none;border-radius:8px;background:#1a4a2e;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">I Accept All Terms</button>
    </div>
  </div>
</div>

<div style="border:10px solid #fff; border-radius:40px; background:rgba(248,248,248,1);">

  <!-- ══════════ HEADER (UNCHANGED) ══════════ -->
  <header class="w-full pt-8 relative z-50 flex items-center justify-between px-4 sm:px-8 md:px-16 lg:px-24">
    <div class="flex-shrink-0">
      <div class="w-14 h-14 rounded-full border border-[#c8a84b] flex items-center justify-center bg-[#1a4a2e] shadow-lg">
        <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="w-10 h-10 object-contain">
      </div>
    </div>
    <nav class="navbar-glass rounded-full pl-8 pr-2 py-2 flex items-center gap-8 ml-auto">
      <div class="hidden lg:flex items-center gap-7">
        <a href="{{ route('home') }}" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Home</a>
        <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Centers</a>
        <a href="#" class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Donate</a>
        <a href="{{ route('join') }}" class="text-white/75 text-[15px] font-medium hover:text-gray-300 transition-colors">Become a Member</a>
        <a href="{{ route('membership-verification') }}" class="text-white/75 text-[15px] font-medium hover:text-gray-300 transition-colors">Verify Membership Status</a>
      </div>
      <div class="flex items-center gap-3">
        <a href="https://isgh.wildapricot.org/Sys/Login" target="_blank" rel="noopener noreferrer" class="bg-white/20 hover:bg-white/30 text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors inline-block text-center">Sign in</a>
        <a href="{{ route('join') }}" style="background:#00d084;" class="hover:bg-[#00b870] text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors shadow-md inline-block text-center">Join Now</a>
      </div>
      <button class="lg:hidden ml-2 pr-2 text-white/70 hover:text-white transition-colors" onclick="openMobileMenu()">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </nav>
  </header>

  <!-- ══════════ HERO (UNCHANGED) ══════════ -->
  <section class="hero-bg min-h-[260px] sm:min-h-[420px] flex items-center justify-center pt-4 sm:pt-8 pb-16 px-4" style="border-bottom-left-radius:50px;border-bottom-right-radius:50px;position:relative;top:-86px;">
    <div class="relative z-10 flex flex-col items-center text-center max-w-3xl mx-auto gap-6 mt-16">
      <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white drop-shadow-md tracking-tight">Join ISGH</h1>
      <p class="text-white/90 text-sm sm:text-base leading-relaxed max-w-lg drop-shadow-sm">
        Your membership supports our Masajid, provides free healthcare at Shifa Clinics, and empowers our
        youth through education. Choose the category that best fits your family and join our legacy of service.
      </p>
      <div class="inline-flex items-center gap-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-full py-2 px-6 mt-2 shadow-lg">
        <div class="flex items-center gap-1 text-yellow-400 text-lg">★ ★ ★ ★ ★</div>
        <span class="text-white text-sm font-medium" style="font-family:'SF Pro regular';">Join 50,000+ active members across Greater Houston.</span>
      </div>
    </div>
  </section>

  <!-- ══════════ MAIN CONTAINER (REDESIGNED) ══════════ -->
  <div class="main-container">
    <div class="membership-grid">

      <!-- ── LEFT SIDE COLUMN ── -->
      <div class="membership-card-side">
        <div class="side-item">
          <img src="{{ asset('images/truck.png') }}" alt="Humanitarian Aid">
          <h3>Humanitarian Aid</h3>
          <p>Join our mission to provide immediate relief to those affected by natural disasters and poverty. Your generosity provides a lifeline to families in their most vulnerable moments.</p>
        </div>
        <div class="side-item">
          <img src="{{ asset('images/molvi.png') }}" alt="Chaplaincy">
          <h3>Chaplaincy</h3>
          <p>ISGH's Chaplaincy Services is dedicated to offering services to Muslim chaplains through endorsement, education & training, and leadership development.</p>
        </div>
        <div class="side-item">
          <img src="{{ asset('images/education_forum.png') }}" alt="Education Forum">
          <h3>Education Forum</h3>
          <p>Provide training & resources for students to realize their full potential and contribute to society. Help us support educational excellence and lifelong learning.</p>
        </div>
        <div class="side-item">
          <img src="{{ asset('images/molvi.png') }}" alt="Youth Programs">
          <h3>Youth Programs</h3>
          <p>The ISGH I-YOUTH program provides programming, leadership, and support with a sustainable focus on our youth and the future of the Muslim community.</p>
        </div>
        <div class="side-item">
          <img src="{{ asset('images/kronic_Academy.png') }}" alt="Quran Academy">
          <h3>Quran Academy</h3>
          <p>Ensure the teaching of the Holy Quran under our expert religious instructors. Give the gift of Islamic education to our youth and family.</p>
        </div>
      </div>

      <!-- ── CENTER CARD ── -->
      <div class="center-card">

        <!-- STEP 1: Choose Membership Type -->
        <div class="step-indicator">
          <div class="step-number">1</div>
          <h2 class="form-section-title">Choose Membership Type</h2>
          <p class="step-subtitle">Please select the membership category that best fits your household.</p>
        </div>

        <div class="membership-selector" style="margin-top:1.5rem;">
          <span class="sel-label">Choose Membership Type</span>
          <select id="membershipSelector" onchange="toggleMembershipForm()">
            <option value="">— Select a Membership Type —</option>
            <!-- <option value="family">Family Membership (Primary and Spouse only) — $40/year</option> -->
            <!-- <option value="individual">Individual Membership — $25/year</option> -->
            <option value="flat">Flat Membership — $20/year</option>
            <option value="checkomatic_family">Checkomatic Membership (Primary and Spouse only) — $10/month</option>
            <option value="checkomatic_individual">Checkomatic Membership Individual — $10/month</option>
            <option value="lifetime_family">Lifetime Membership (Family - Primary and Spouse) — $1500/lifetime</option>
            <option value="lifetime_individual">Lifetime Membership (Individual) — $1000/lifetime</option>
          </select>
        </div>

        <!-- ════════════════════════════════════════
             FAMILY MEMBERSHIP SECTION
        ════════════════════════════════════════ -->
        <div id="familyMembershipSection" style="display:none;">

          <!-- Banner -->
          <div class="membership-banner banner-family">
            <div class="banner-left">
              <p class="banner-title">Family Membership (Primary & Spouse)</p>
              <p class="banner-subtitle">Includes both primary member and spouse</p>
              <div class="banner-badges">
                <span class="banner-badge">✔ Voting Privileges</span>
                <span class="banner-badge">✔ Medical Care</span>
                <span class="banner-badge">✔ Education</span>
              </div>
            </div>
            <div class="banner-price">
              <p class="price-amount">$40</p>
              <p class="price-period">Per Year</p>
            </div>
          </div>

          <!-- STEP 2: Primary Information -->
          <div class="step-indicator">
            <div class="step-number">2</div>
            <h3 class="form-section-title">Primary Information</h3>
          </div>
          <div style="text-align:center;">
            <div class="scan-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              Scan ID Card
            </div>
          </div>

          <div class="fields-stack">
            <div class="field">
              <label>First Name <span>*</span></label>
              <input type="text" placeholder="Ahmad">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            </div>
            <div class="field">
              <label>Middle Name</label>
              <input type="text" id="fam_middle_name" placeholder="Middle Name (Optional)">
            </div>
            <div class="field">
              <label>Last Name <span>*</span></label>
              <input type="text" placeholder="Ali">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            </div>
            <div class="field">
              <label>Email Address <span>*</span></label>
              <input type="email" placeholder="ahmad@example.com" value="{{ $verifiedEmail }}" readonly style="background:#f3f4f6;cursor:not-allowed;">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
            </div>
            <div class="fields-stack">
            <div class="field">
              <label>Phone Number <span>*</span></label>
              <input type="text" id="fam_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)">
              <div id="fam_phone_msg" class="phone-msg"></div>
            </div>
          </div>
            <div class="field">
              <label>Date of Birth <span>*</span></label>
              <input type="text" placeholder="MM/DD/YYYY">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            </div>
            <div class="field">
              <label>TX DL # or ID Card # <span>*</span></label>
              <input type="text" placeholder="e.g. TX7234578">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
            </div>
            <div class="field">
              <label>Street Address <span>*</span></label>
              <input type="text" id="fam_street" placeholder="123 Main Street" oninput="autoFillSpouseAddresses('fam')">
            </div>
            <div class="field">
              <label>City <span>*</span></label>
              <input type="text" id="fam_city" placeholder="Houston" oninput="autoFillSpouseAddresses('fam')">
              <div class="field-icon"><svg width="14" height="10" viewBox="0 0 14 10" fill="none"><path d="M1 5L5 9L13 1" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            </div>
            
            <div class="field">
              <label>ZIP Code <span>*</span></label>
              <input type="text" id="fam_zip" placeholder="77001" onblur="validateZip(this,'fam')" oninput="autoFillSpouseAddresses('fam')">
              <div id="fam_zip_msg" class="zip-msg"></div>
            </div>
            <div class="field zone-field" id="fam_center_field" style="display:none;">
              <label>Center / Zone</label>
              <div id="fam_center_display"></div>
            </div>
            <div class="field">
              <label>State <span>*</span></label>
              <select id="fam_state" onchange="autoFillSpouseAddresses('fam')"><option selected>Texas</option></select>
            </div>
          </div>

          <div class="section-divider"></div>

          <!-- STEP 3: Spouse Information -->
          <div class="step-indicator">
            <div class="step-number">3</div>
            <h3 class="form-section-title">Spouse Information</h3>
          </div>

          <div id="fam_spouses_container">
            <div class="spouse-block" id="fam_spouse_block_0">
              <div class="spouse-block-header">
                <div class="member-tag">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  Spouse 1
                </div>
              </div>
              <div class="fields-stack">
                <div class="field"><label>First Name <span>*</span></label><input type="text" placeholder="Fatima"></div>
                <div class="field"><label>Last Name <span>*</span></label><input type="text" placeholder="Ali"></div>
                <div class="field"><label>Middle Name</label><input type="text" id="fam_spouse_0_middle_name" placeholder="Middle Name (Optional)"></div>
                <div class="field"><label>Date of Birth <span>*</span></label><input type="text" placeholder="MM/DD/YYYY"></div>
                <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="fam_spouse_0_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="fam_spouse_0_phone_msg" class="phone-msg"></div></div>
                <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" id="fam_spouse_0_txdl" placeholder="e.g. TX7234578"></div>
                <div class="field"><label>Email Address</label><input type="email" id="fam_spouse_0_email" placeholder="spouse@example.com"></div>
                <div class="field"><label>Gender</label><select id="fam_spouse_0_gender"><option value="">Select Gender</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                <div class="field"><label>Street Address</label><input type="text" id="fam_spouse_0_street" placeholder="Auto-filled from primary"></div>
                <div class="field"><label>City</label><input type="text" id="fam_spouse_0_city" placeholder="Auto-filled from primary"></div>
                <div class="field"><label>ZIP Code</label><input type="text" id="fam_spouse_0_zip" placeholder="Auto-filled from primary"></div>
                <div class="field"><label>State</label><select id="fam_spouse_0_state"><option selected>Texas</option></select></div>
              </div>
            </div>
          </div>
          <!-- <button class="btn-add-member" type="button" onclick="addSpouseBlock('fam')">
            <div style="width:22px;height:22px;border-radius:50%;border:2px dashed #d1d5db;display:flex;align-items:center;justify-content:center;font-size:0.9rem;line-height:1;">+</div>
            Add Another Spouse (Optional)
          </button> -->

          <div class="section-divider"></div>

          <!-- STEP 4: Payment Information -->
          <div class="step-indicator">
            <!-- <div class="step-number">4</div> -->
            <h3 class="form-section-title">Order Summary</h3>
          </div>

          

          <!-- Order Summary -->
          <div class="order-summary" style="margin-top:1.15rem;">
            <div class="order-summary-header">
              <!-- <span class="order-summary-title">Order Summary</span> -->
            </div>
            <div class="order-row"><span>Membership Type</span><span>FAMILY MEMBERSHIP</span></div>
            <div class="order-row"><span>Membership Fee</span><span>$40.00</span></div>
            <div class="order-total">
              <span class="order-total-label">Total</span>
              <span class="order-total-amount">$40.00</span>
            </div>
          </div>

          <div class="section-divider"></div>

          <!-- STEP 5: Terms & Agreements -->
          <div class="step-indicator">
            <div class="step-number">5</div>
            <h3 class="form-section-title">Terms &amp; Agreements</h3>
          </div>

          <div class="terms-list flex flex-col gap-0">
            <div class="checkbox-box"><input type="checkbox" id="ft1" class="custom-checkbox"><label for="ft1">I agree to the <a href="#" class="text-emerald-600 font-bold underline" onclick="openTermsModal(event)">Terms and Conditions</a> of the Islamic Society of Greater Houston.</label></div>
          </div>

          <button id="fam_submit_btn" class="btn-submit" type="button" onclick="submitMembership('family')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Complete Registration &amp; Pay $40.00
          </button>
          <p class="secure-note">🔒 Your payment information is secured with Stripe.</p>
        </div>
        <!-- /familyMembershipSection -->


        <!-- ════════════════════════════════════════
             INDIVIDUAL MEMBERSHIP SECTION
        ════════════════════════════════════════ -->
        <div id="individualMembershipSection" style="display:none;">

          <!-- Banner -->
          <div class="membership-banner banner-individual">
            <div class="banner-left">
              <p class="banner-title">Individual Membership</p>
              <p class="banner-subtitle">Individual membership only</p>
              <div class="banner-badges">
                <span class="banner-badge">✔ Voting Privileges</span>
                <span class="banner-badge">✔ Medical Care</span>
                <span class="banner-badge">✔ Education</span>
              </div>
            </div>
            <div class="banner-price">
              <p class="price-amount">$25</p>
              <p class="price-period">Per Year</p>
            </div>
          </div>

          <!-- STEP 2: Primary Information -->
          <div class="step-indicator">
            <div class="step-number">2</div>
            <h3 class="form-section-title">Primary Information</h3>
          </div>
          <div style="text-align:center;">
            <div class="scan-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              Scan ID Card
            </div>
          </div>

          <div class="fields-stack">
            <div class="field">
              <label>First Name <span>*</span></label>
              <input type="text" placeholder="Ahmad">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            </div>
            <div class="field">
              <label>Middle Name</label>
              <input type="text" id="ind_middle_name" placeholder="Middle Name (Optional)">
            </div>
            <div class="field">
              <label>Last Name <span>*</span></label>
              <input type="text" placeholder="Ali">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            </div>
            <div class="field">
              <label>Email Address <span>*</span></label>
              <input type="email" placeholder="ahmad@example.com" value="{{ $verifiedEmail }}" readonly style="background:#f3f4f6;cursor:not-allowed;">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
            </div>
            <div class="fields-stack">
            <div class="field">
              <label>Phone Number <span>*</span></label>
              <input type="text" id="ind_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)">
              <div id="ind_phone_msg" class="phone-msg"></div>
            </div>
          </div>
            <div class="field">
              <label>Date of Birth <span>*</span></label>
              <input type="text" placeholder="MM/DD/YYYY">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            </div>
            <div class="field">
              <label>TX DL # or ID Card # <span>*</span></label>
              <input type="text" placeholder="e.g. TX7234578">
              <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
            </div>
            <div class="field">
              <label>Street Address <span>*</span></label>
              <input type="text" placeholder="123 Main Street">
            </div>
            <div class="field">
              <label>City <span>*</span></label>
              <input type="text" placeholder="Houston">
              <div class="field-icon"><svg width="14" height="10" viewBox="0 0 14 10" fill="none"><path d="M1 5L5 9L13 1" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            </div>
            
            <div class="field">
              <label>ZIP Code <span>*</span></label>
              <input type="text" id="ind_zip" placeholder="77001" onblur="validateZip(this,'ind')">
              <div id="ind_zip_msg" class="zip-msg"></div>
            </div>
            <div class="field zone-field" id="ind_center_field" style="display:none;">
              <label>Center / Zone</label>
              <div id="ind_center_display"></div>
            </div>
            <div class="field">
              <label>State <span>*</span></label>
              <select id="ind_state"><option selected>Texas</option></select>
            </div>
          </div>

          <div class="section-divider"></div>

          <!-- STEP 3: Payment Information -->
          <div class="step-indicator">
            <div class="step-number">3</div>
            <h3 class="form-section-title">Payment Information</h3>
          </div>

          

          <!-- Order Summary -->
          <div class="order-summary" style="margin-top:1.15rem;">
            <div class="order-summary-header">
              <span class="order-summary-title">Order Summary</span>
            </div>
            <div class="order-row"><span>Membership Type</span><span>INDIVIDUAL MEMBERSHIP</span></div>
            <div class="order-row"><span>Membership Fee</span><span>$25.00</span></div>
            <div class="order-total">
              <span class="order-total-label">Total</span>
              <span class="order-total-amount">$25.00</span>
            </div>
          </div>

          <div class="section-divider"></div>

          <!-- STEP 4: Terms & Agreements -->
          <div class="step-indicator">
            <div class="step-number">4</div>
            <h3 class="form-section-title">Terms &amp; Agreements</h3>
          </div>

          <div class="terms-list flex flex-col gap-0">
            <div class="checkbox-box"><input type="checkbox" id="it1" class="custom-checkbox"><label for="it1">I agree to the <a href="#" class="text-emerald-600 font-bold underline" onclick="openTermsModal(event)">Terms and Conditions</a> of the Islamic Society of Greater Houston.</label></div>
          </div>

          <button id="ind_submit_btn" class="btn-submit" type="button" onclick="submitMembership('individual')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Complete Registration &amp; Pay $25.00
          </button>
          <p class="secure-note">🔒 Your payment information is secured with Stripe.</p>
        </div>
        <!-- /individualMembershipSection -->


        <!-- ════════════════════════════════════════
             FLAT MEMBERSHIP SECTION
        ════════════════════════════════════════ -->
        <div id="flatMembershipSection" style="display:none;">

          <div class="membership-banner banner-flat">
            <div class="banner-left">
              <p class="banner-title">Flat Membership</p>
              <p class="banner-subtitle">All household members for one flat rate</p>
              <div class="banner-badges">
                <span class="banner-badge">✔ Voting Privileges</span>
                <span class="banner-badge">✔ Medical Care</span>
                <span class="banner-badge">✔ Education</span>
              </div>
            </div>
            <div class="banner-price">
              <p class="price-amount">$20</p>
              <p class="price-period">Per Year</p>
            </div>
          </div>

          <!-- STEP 2: Primary Information -->
          <div class="step-indicator">
            <div class="step-number">2</div>
            <h3 class="form-section-title">Primary Information</h3>
          </div>
          <div style="text-align:center;">
            <div class="scan-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              Scan ID Card
            </div>
          </div>

          <div class="fields-stack">
            <div class="field"><label>Email Address <span>*</span></label><input type="email" placeholder="ahmad@example.com" value="{{ $verifiedEmail }}" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
            <div class="field"><label>First Name <span>*</span></label><input type="text" placeholder="Ahmad"></div>
             <div class="field"><label>Middle Name</label><input type="text" id="flt_middle_name" placeholder="Middle Name (Optional)"></div>
            <div class="field"><label>Last Name <span>*</span></label><input type="text" placeholder="Ali"></div>
            <div class="fields-stack">
            <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="flt_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="flt_phone_msg" class="phone-msg"></div></div>
            </div>
            <div class="field"><label>Date of Birth <span>*</span></label><input type="text" placeholder="MM/DD/YYYY"></div>
            <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" placeholder="e.g. TX7234578"></div>
            <div class="field"><label>Street Address <span>*</span></label><input type="text" id="flt_street" placeholder="123 Main Street" oninput="autoFillFlatMemberAddresses()"></div>
            <div class="field"><label>City <span>*</span></label><input type="text" id="flt_city" placeholder="Houston" oninput="autoFillFlatMemberAddresses()"></div>

            <div class="field"><label>ZIP Code <span>*</span></label><input type="text" id="flt_zip" placeholder="77001" onblur="validateZip(this,'flt')" oninput="autoFillFlatMemberAddresses()"><div id="flt_zip_msg" class="zip-msg"></div></div>
            <div class="field zone-field" id="flt_center_field" style="display:none;"><label>Center / Zone</label><div id="flt_center_display"></div></div>
            <div class="field"><label>State <span>*</span></label><select id="flt_state" onchange="autoFillFlatMemberAddresses()"><option selected>Texas</option></select></div>
          </div>

          <div class="section-divider"></div>

          <!-- STEP 3: Family Members -->
          <div class="step-indicator">
            <div class="step-number">3</div>
            <h3 class="form-section-title">Pay for Family Members</h3>
          </div>

          <div class="flat-pay-note">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Pay for your family members who are 18 and over and reside in same addressty.
          </div>

          <div id="flat_members_container">
            <!-- <div class="member-card" id="flat_member_block_0">
              <div class="member-header">
                <div class="member-tag">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  Member 1
                </div>
                <button type="button" class="btn-remove-block" onclick="removeFlatBlock('flat_member_block_0')">✕ Remove</button>
              </div>
              <div class="fields-stack">
                <div class="field"><label>First Name <span>*</span></label><input type="text" id="flat_member_0_first_name" placeholder="Ahmad"></div>
                <div class="field"><label>Middle Name</label><input type="text" id="flat_member_0_middle_name" placeholder="Middle Name (Optional)"></div>
                <div class="field"><label>Last Name <span>*</span></label><input type="text" id="flat_member_0_last_name" placeholder="Ali"></div>
                <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="flat_member_0_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="flat_member_0_phone_msg" class="phone-msg"></div></div>
                <div class="field"><label>Date of Birth <span>*</span></label><input type="text" id="flat_member_0_dob" placeholder="MM/DD/YYYY"></div>
                <div class="field"><label>TX DL # or ID Card #</label><input type="text" id="flat_member_0_txdl" placeholder="e.g. TX7234578"></div>
                <div class="field"><label>Relation <span>*</span></label><select id="flat_member_0_relation"><option value="">Select Relation</option><option>Child</option><option>Sibling</option><option>Parent</option></select></div>
                <div class="field"><label>Street Address</label><input type="text" id="flat_member_0_street" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
                <div class="field"><label>City</label><input type="text" id="flat_member_0_city" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
                <div class="field"><label>ZIP Code</label><input type="text" id="flat_member_0_zip" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
                <div class="field"><label>State</label><input type="text" id="flat_member_0_state" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
              </div>
            </div> -->
          </div>

          <button class="btn-add-member" type="button" onclick="addFlatMemberBlock()">
            <div style="width:22px;height:22px;border-radius:50%;border:2px dashed #d1d5db;display:flex;align-items:center;justify-content:center;font-size:0.9rem;line-height:1;">+</div>
            Add Another Family Member (Optional)
          </button>

          <div class="section-divider"></div>

          <!-- STEP 4: Payment -->
          <div class="step-indicator">
            <div class="step-number">4</div>
            <h3 class="form-section-title">Payment Information</h3>
          </div>

          

          <div class="order-summary" style="margin-top:1.15rem;">
            <div class="order-summary-header"><span class="order-summary-title">Order Summary</span></div>
            <div class="order-row"><span>Membership Type</span><span>FLAT MEMBERSHIP</span></div>
            <div class="order-row"><span id="flat_member_count">2 members × $20</span><span id="flat_fee_display">$40.00</span></div>
            <div class="order-total"><span class="order-total-label">Total</span><span class="order-total-amount" id="flat_total_display">$40.00</span></div>
          </div>

          <div class="section-divider"></div>

          <!-- STEP 5: Terms -->
          <div class="step-indicator">
            <div class="step-number">5</div>
            <h3 class="form-section-title">Terms &amp; Agreements</h3>
          </div>
          <div class="terms-list flex flex-col gap-0">
            <div class="checkbox-box"><input type="checkbox" id="flt1" class="custom-checkbox"><label for="flt1">I agree to the <a href="#" class="text-emerald-600 font-bold underline" onclick="openTermsModal(event)">Terms and Conditions</a> of the Islamic Society of Greater Houston.</label></div>
          </div>
          <button id="flt_submit_btn" class="btn-submit" type="button" onclick="submitMembership('flat')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Complete Registration &amp; Pay $20.00</button>
          <p class="secure-note">🔒 Your payment information is secured with Stripe.</p>
        </div>
        <!-- /flatMembershipSection -->


        <!-- ════════════════════════════════════════
             CHECKOMATIC FAMILY SECTION
        ════════════════════════════════════════ -->
        <div id="checkomaticFamilySection" style="display:none;">

          <div class="membership-banner banner-checkomatic">
            <div class="banner-left">
              <p class="banner-title">Checkomatic Membership (Family)</p>
              <p class="banner-subtitle">Includes both primary member and spouse</p>
              <div class="banner-badges">
                <span class="banner-badge">✔ Voting Privileges</span>
                <span class="banner-badge">✔ Medical Care</span>
                <span class="banner-badge">✔ Education</span>
              </div>
            </div>
            <div class="banner-price">
              <p class="price-amount">$10</p>
              <p class="price-period">Per Month</p>
            </div>
          </div>

          <!-- STEP 2: Primary Information -->
          <div class="step-indicator"><div class="step-number">2</div><h3 class="form-section-title">Primary Information</h3></div>
          <div style="text-align:center;"><div class="scan-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Scan ID Card</div></div>
          <div class="fields-stack">
            <div class="field"><label>Email Address <span>*</span></label><input type="email" placeholder="ahmad@example.com" value="{{ $verifiedEmail }}" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
            <div class="field"><label>First Name <span>*</span></label><input type="text" placeholder="Ahmad"></div>
            <div class="field"><label>Middle Name</label><input type="text" id="ckf_middle_name" placeholder="Middle Name (Optional)"></div>
            <div class="field"><label>Last Name <span>*</span></label><input type="text" placeholder="Ali"></div>
            <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="ckf_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="ckf_phone_msg" class="phone-msg"></div></div>
            <div class="field"><label>Date of Birth <span>*</span></label><input type="text" placeholder="MM/DD/YYYY"></div>
            <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" placeholder="e.g. TX7234578"></div>
            <div class="field"><label>Street Address <span>*</span></label><input type="text" id="ckf_street" placeholder="123 Main Street" oninput="autoFillSpouseAddresses('ckf')"></div>
            <div class="field"><label>City <span>*</span></label><input type="text" id="ckf_city" placeholder="Houston" oninput="autoFillSpouseAddresses('ckf')"></div>
            <div class="field"><label>ZIP Code <span>*</span></label><input type="text" id="ckf_zip" placeholder="77001" onblur="validateZip(this,'ckf')" oninput="autoFillSpouseAddresses('ckf')"><div id="ckf_zip_msg" class="zip-msg"></div></div>
            <div class="field zone-field" id="ckf_center_field" style="display:none;"><label>Center / Zone</label><div id="ckf_center_display"></div></div>
            <div class="field"><label>State <span>*</span></label><select id="ckf_state" onchange="autoFillSpouseAddresses('ckf')"><option selected>Texas</option></select></div>
          </div>

          <div class="section-divider"></div>

          <!-- STEP 3: Spouse Information -->
          <div class="step-indicator"><div class="step-number">3</div><h3 class="form-section-title">Spouse Information</h3></div>

          <div id="ckf_spouses_container">
            <div class="spouse-block" id="ckf_spouse_block_0">
              <div class="spouse-block-header">
                <div class="member-tag"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Spouse 1</div>
              </div>
              <div class="fields-stack">
                <div class="field"><label>Email Address</label><input type="email" id="ckf_spouse_0_email" placeholder="spouse@example.com"></div>
                <div class="field"><label>First Name <span>*</span></label><input type="text" placeholder="Fatima"></div>
                <div class="field"><label>Middle Name</label><input type="text" id="ckf_spouse_0_middle_name" placeholder="Middle Name (Optional)"></div>
                <div class="field"><label>Last Name <span>*</span></label><input type="text" placeholder="Ali"></div>
                <div class="field"><label>Date of Birth <span>*</span></label><input type="text" placeholder="MM/DD/YYYY"></div>
                <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="ckf_spouse_0_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="ckf_spouse_0_phone_msg" class="phone-msg"></div></div>
                <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" id="ckf_spouse_0_txdl" placeholder="e.g. TX7234578"></div>
                <div class="field"><label>Gender</label><select id="ckf_spouse_0_gender"><option value="">Select Gender</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                <div class="field"><label>Street Address</label><input type="text" id="ckf_spouse_0_street" placeholder="Auto-filled from primary"></div>
                <div class="field"><label>City</label><input type="text" id="ckf_spouse_0_city" placeholder="Auto-filled from primary"></div>
                <div class="field"><label>ZIP Code</label><input type="text" id="ckf_spouse_0_zip" placeholder="Auto-filled from primary"></div>
                <div class="field"><label>State</label><select id="ckf_spouse_0_state"><option selected>Texas</option></select></div>
              </div>
            </div>
          </div>
          <!-- <button class="btn-add-member" type="button" onclick="addSpouseBlock('ckf')">
            <div style="width:22px;height:22px;border-radius:50%;border:2px dashed #d1d5db;display:flex;align-items:center;justify-content:center;font-size:0.9rem;line-height:1;">+</div>
            Add Another Spouse (Optional)
          </button> -->

          <div class="section-divider"></div>

          <!-- STEP 4: Payment -->
          <div class="step-indicator"><div class="step-number">4</div><h3 class="form-section-title">Payment Information</h3></div>

          

          <div class="order-summary" style="margin-top:1.15rem;">
            <div class="order-summary-header"><span class="order-summary-title">Order Summary</span><span class="order-badge">Monthly billing — cancel anytime</span></div>
            <div class="order-row"><span>Membership Type</span><span>CHECKOMATIC FAMILY</span></div>
            <div class="order-row"><span>Membership Fee</span><span>$10.00 / month</span></div>
            <div class="order-total"><span class="order-total-label">Monthly Total</span><span class="order-total-amount">$10.00</span></div>
          </div>

          <div class="section-divider"></div>

          <!-- STEP 5: Terms -->
          <div class="step-indicator"><div class="step-number">5</div><h3 class="form-section-title">Terms &amp; Agreements</h3></div>
          <div class="terms-list flex flex-col gap-0">
            <div class="checkbox-box"><input type="checkbox" id="ckft1" class="custom-checkbox"><label for="ckft1">I agree to the <a href="#" class="text-emerald-600 font-bold underline" onclick="openTermsModal(event)">Terms and Conditions</a> of the Islamic Society of Greater Houston.</label></div>
          </div>
          <button id="ckf_submit_btn" class="btn-submit" type="button" onclick="submitMembership('checkomatic_family')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Complete Registration &amp; Pay $10.00/mo</button>
          <p class="secure-note">🔒 Your payment information is secured with Stripe.</p>
        </div>
        <!-- /checkomaticFamilySection -->


        <!-- ════════════════════════════════════════
             CHECKOMATIC INDIVIDUAL SECTION
        ════════════════════════════════════════ -->
        <div id="checkomaticIndividualSection" style="display:none;">

          <div class="membership-banner banner-checkomatic">
            <div class="banner-left">
              <p class="banner-title">Checkomatic Membership (Individual)</p>
              <p class="banner-subtitle">Individual membership only</p>
              <div class="banner-badges">
                <span class="banner-badge">✔ Voting Privileges</span>
                <span class="banner-badge">✔ Medical Care</span>
                <span class="banner-badge">✔ Education</span>
              </div>
            </div>
            <div class="banner-price">
              <p class="price-amount">$10</p>
              <p class="price-period">Per Month</p>
            </div>
          </div>

          <div class="step-indicator"><div class="step-number">2</div><h3 class="form-section-title">Primary Information</h3></div>
          <div style="text-align:center;"><div class="scan-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Scan ID Card</div></div>
          <div class="fields-stack">
            <div class="field"><label>Email Address <span>*</span></label><input type="email" placeholder="ahmad@example.com" value="{{ $verifiedEmail }}" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
            <div class="field"><label>First Name <span>*</span></label><input type="text" placeholder="Ahmad"></div>
            <div class="field"><label>Middle Name</label><input type="text" id="cki_middle_name" placeholder="Middle Name (Optional)"></div>
            <div class="field"><label>Last Name <span>*</span></label><input type="text" placeholder="Ali"></div>
            <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="cki_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="cki_phone_msg" class="phone-msg"></div></div>
            <div class="field"><label>Date of Birth <span>*</span></label><input type="text" placeholder="MM/DD/YYYY"></div>
            <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" placeholder="e.g. TX7234578"></div>
            <div class="field"><label>Street Address <span>*</span></label><input type="text" placeholder="123 Main Street"></div>
            <div class="field"><label>City <span>*</span></label><input type="text" placeholder="Houston"></div>
            <div class="field"><label>ZIP Code <span>*</span></label><input type="text" id="cki_zip" placeholder="77001" onblur="validateZip(this,'cki')"><div id="cki_zip_msg" class="zip-msg"></div></div>
            <div class="field zone-field" id="cki_center_field" style="display:none;"><label>Center / Zone</label><div id="cki_center_display"></div></div>
            <div class="field"><label>State <span>*</span></label><select id="cki_state"><option selected>Texas</option></select></div>
          </div>

          <div class="section-divider"></div>

          <div class="step-indicator"><div class="step-number">3</div><h3 class="form-section-title">Payment Information</h3></div>

          <div class="order-summary" style="margin-top:1.15rem;">
            <div class="order-summary-header"><span class="order-summary-title">Order Summary</span><span class="order-badge">Monthly billing — cancel anytime</span></div>
            <div class="order-row"><span>Membership Type</span><span>CHECKOMATIC INDIVIDUAL</span></div>
            <div class="order-row"><span>Membership Fee</span><span>$10.00 / month</span></div>
            <div class="order-total"><span class="order-total-label">Monthly Total</span><span class="order-total-amount">$10.00</span></div>
          </div>

          <div class="section-divider"></div>

          <div class="step-indicator"><div class="step-number">4</div><h3 class="form-section-title">Terms &amp; Agreements</h3></div>
          <div class="terms-list flex flex-col gap-0">
            <div class="checkbox-box"><input type="checkbox" id="ckit1" class="custom-checkbox"><label for="ckit1">I agree to the <a href="#" class="text-emerald-600 font-bold underline" onclick="openTermsModal(event)">Terms and Conditions</a> of the Islamic Society of Greater Houston.</label></div>
          </div>
          <button id="cki_submit_btn" class="btn-submit" type="button" onclick="submitMembership('checkomatic_individual')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Complete Registration &amp; Pay $10.00/mo</button>
          <p class="secure-note">🔒 Your payment information is secured with Stripe.</p>
        </div>
        <!-- /checkomaticIndividualSection -->


        <!-- ════════════════════════════════════════
             LIFETIME FAMILY SECTION
        ════════════════════════════════════════ -->
        <div id="lifetimeFamilySection" style="display:none;">

          <div class="membership-banner banner-lifetime">
            <div class="banner-left">
              <p class="banner-title">Lifetime Membership (Family - Primary and Spouse)</p>
              <p class="banner-subtitle">Lifetime Membership for Family only</p>
              <div class="banner-badges">
                <span class="banner-badge">✔ All family membership benefits</span>
                <span class="banner-badge">✔ One time payment</span>
                <span class="banner-badge">✔ Legacy membership status</span>
                <span class="banner-badge">✔ Priority services</span>
              </div>
            </div>
            <div class="banner-price">
              <p class="price-amount">$1500</p>
              <p class="price-period">LIFETIME Unlimited</p>
            </div>
          </div>

          <div class="step-indicator"><div class="step-number">2</div><h3 class="form-section-title">Primary Information</h3></div>
          <div style="text-align:center;"><div class="scan-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Scan ID Card</div></div>
          <div class="fields-stack">
            <div class="field"><label>Email Address <span>*</span></label><input type="email" placeholder="ahmad@example.com" value="{{ $verifiedEmail }}" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
            <div class="field"><label>First Name <span>*</span></label><input type="text" placeholder="Ahmad"></div>
            <div class="field"><label>Middle Name</label><input type="text" id="ltf_middle_name" placeholder="Middle Name (Optional)"></div>
            <div class="field"><label>Last Name <span>*</span></label><input type="text" placeholder="Ali"></div>
            <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="ltf_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="ltf_phone_msg" class="phone-msg"></div></div>
            <div class="field"><label>Date of Birth <span>*</span></label><input type="text" placeholder="MM/DD/YYYY"></div>
            <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" placeholder="e.g. TX7234578"></div>
            <div class="field"><label>Street Address <span>*</span></label><input type="text" id="ltf_street" placeholder="123 Main Street" oninput="autoFillSpouseAddresses('ltf')"></div>
            <div class="field"><label>City <span>*</span></label><input type="text" id="ltf_city" placeholder="Houston" oninput="autoFillSpouseAddresses('ltf')"></div>
            <div class="field"><label>ZIP Code <span>*</span></label><input type="text" id="ltf_zip" placeholder="77001" onblur="validateZip(this,'ltf')" oninput="autoFillSpouseAddresses('ltf')"><div id="ltf_zip_msg" class="zip-msg"></div></div>
            <div class="field zone-field" id="ltf_center_field" style="display:none;"><label>Center / Zone</label><div id="ltf_center_display"></div></div>
            <div class="field"><label>State <span>*</span></label><select id="ltf_state" onchange="autoFillSpouseAddresses('ltf')"><option selected>Texas</option></select></div>
          </div>

          <div class="section-divider"></div>

          <div class="step-indicator"><div class="step-number">3</div><h3 class="form-section-title">Spouse Information</h3></div>

          <div id="ltf_spouses_container">
            <div class="spouse-block" id="ltf_spouse_block_0">
              <div class="spouse-block-header">
                <div class="member-tag"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Spouse 1</div>
              </div>
              <div class="fields-stack">
                <div class="field"><label>Email Address</label><input type="email" id="ltf_spouse_0_email" placeholder="spouse@example.com"></div>
                <div class="field"><label>First Name <span>*</span></label><input type="text" placeholder="Fatima"></div>
                <div class="field"><label>Middle Name</label><input type="text" id="ltf_spouse_0_middle_name" placeholder="Middle Name (Optional)"></div>
                <div class="field"><label>Last Name <span>*</span></label><input type="text" placeholder="Ali"></div>
                <div class="field"><label>Date of Birth <span>*</span></label><input type="text" placeholder="MM/DD/YYYY"></div>
                <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="ltf_spouse_0_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="ltf_spouse_0_phone_msg" class="phone-msg"></div></div>
                <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" id="ltf_spouse_0_txdl" placeholder="e.g. TX7234578"></div>
                <div class="field"><label>Gender</label><select id="ltf_spouse_0_gender"><option value="">Select Gender</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                <div class="field"><label>Street Address</label><input type="text" id="ltf_spouse_0_street" placeholder="Auto-filled from primary"></div>
                <div class="field"><label>City</label><input type="text" id="ltf_spouse_0_city" placeholder="Auto-filled from primary"></div>
                <div class="field"><label>ZIP Code</label><input type="text" id="ltf_spouse_0_zip" placeholder="Auto-filled from primary"></div>
                <div class="field"><label>State</label><select id="ltf_spouse_0_state"><option selected >Texas</option></select></div>
              </div>
            </div>
          </div>
          <!-- <button class="btn-add-member" type="button" onclick="addSpouseBlock('ltf')">
            <div style="width:22px;height:22px;border-radius:50%;border:2px dashed #d1d5db;display:flex;align-items:center;justify-content:center;font-size:0.9rem;line-height:1;">+</div>
            Add Another Spouse (Optional)
          </button> -->

          <div class="section-divider"></div>

          <div class="step-indicator"><div class="step-number">4</div><h3 class="form-section-title">Payment Information</h3></div>

          <div class="order-summary" style="margin-top:1.15rem;">
            <div class="order-summary-header"><span class="order-summary-title">Order Summary</span></div>
            <div class="order-row"><span>Membership Type</span><span>LIFETIME FAMILY</span></div>
            <div class="order-row"><span>Membership Fee</span><span>$1,500.00 / Lifetime</span></div>
            <div class="order-total"><span class="order-total-label">Total</span><span class="order-total-amount">$1,500.00</span></div>
          </div>

          <div class="section-divider"></div>

          <div class="step-indicator"><div class="step-number">5</div><h3 class="form-section-title">Terms &amp; Agreements</h3></div>
          <div class="terms-list flex flex-col gap-0">
            <div class="checkbox-box"><input type="checkbox" id="lft1" class="custom-checkbox"><label for="lft1">I agree to the <a href="#" class="text-emerald-600 font-bold underline" onclick="openTermsModal(event)">Terms and Conditions</a> of the Islamic Society of Greater Houston.</label></div>
          </div>
          <button id="ltf_submit_btn" class="btn-submit" type="button" onclick="submitMembership('lifetime_family')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Complete Registration &amp; Pay $1,500.00</button>
          <p class="secure-note">🔒 Your payment information is secured with Stripe.</p>
        </div>
        <!-- /lifetimeFamilySection -->


        <!-- ════════════════════════════════════════
             LIFETIME INDIVIDUAL SECTION
        ════════════════════════════════════════ -->
        <div id="lifetimeIndividualSection" style="display:none;">

          <div class="membership-banner banner-lifetime">
            <div class="banner-left">
              <p class="banner-title">Lifetime Membership (Individual)</p>
              <p class="banner-subtitle">Lifetime Membership for Individual only</p>
              <div class="banner-badges">
                <span class="banner-badge">✔ All individual membership benefits</span>
                <span class="banner-badge">✔ One time payment</span>
                <span class="banner-badge">✔ Legacy membership status</span>
                <span class="banner-badge">✔ Priority services</span>
              </div>
            </div>
            <div class="banner-price">
              <p class="price-amount">$1000</p>
              <p class="price-period">LIFETIME Unlimited</p>
            </div>
          </div>

          <div class="step-indicator"><div class="step-number">2</div><h3 class="form-section-title">Primary Information</h3></div>
          <div style="text-align:center;"><div class="scan-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Scan ID Card</div></div>
          <div class="fields-stack">
            <div class="field"><label>Email Address <span>*</span></label><input type="email" placeholder="ahmad@example.com" value="{{ $verifiedEmail }}" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
            <div class="field"><label>First Name <span>*</span></label><input type="text" placeholder="Ahmad"></div>
            <div class="field"><label>Middle Name</label><input type="text" id="lti_middle_name" placeholder="Middle Name (Optional)"></div>
            <div class="field"><label>Last Name <span>*</span></label><input type="text" placeholder="Ali"></div>
            <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="lti_phone" placeholder="e.g. (832) 555-0100 or +1 713-555-0199" onblur="validateUsPhone(this)"><div id="lti_phone_msg" class="phone-msg"></div></div>
            <div class="field"><label>Date of Birth <span>*</span></label><input type="text" placeholder="MM/DD/YYYY"></div>
            <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" placeholder="e.g. TX7234578"></div>
            <div class="field"><label>Street Address <span>*</span></label><input type="text" placeholder="123 Main Street"></div>
            <div class="field"><label>City <span>*</span></label><input type="text" placeholder="Houston"></div>
            <div class="field"><label>ZIP Code <span>*</span></label><input type="text" id="lti_zip" placeholder="77001" onblur="validateZip(this,'lti')"><div id="lti_zip_msg" class="zip-msg"></div></div>
            <div class="field zone-field" id="lti_center_field" style="display:none;"><label>Center / Zone</label><div id="lti_center_display"></div></div>
            <div class="field"><label>State <span>*</span></label><select id="lti_state"><option selected>Texas</option></select></div>
          </div>

          <div class="section-divider"></div>

          <div class="step-indicator"><div class="step-number">3</div><h3 class="form-section-title">Payment Information</h3></div>

          <div class="order-summary">
            <div class="order-summary-header"><span class="order-summary-title">Order Summary</span></div>
            <div class="order-row"><span>Membership Type</span><span>LIFETIME INDIVIDUAL</span></div>
            <div class="order-row"><span>Membership Fee</span><span>$1,000.00 / Lifetime</span></div>
            <div class="order-total"><span class="order-total-label">Total</span><span class="order-total-amount">$1,000.00</span></div>
          </div>

          <div class="section-divider"></div>

          <div class="step-indicator"><div class="step-number">4</div><h3 class="form-section-title">Terms &amp; Agreements</h3></div>
          <div class="terms-list flex flex-col gap-0">
            <div class="checkbox-box"><input type="checkbox" id="lit1" class="custom-checkbox"><label for="lit1">I agree to the <a href="#" class="text-emerald-600 font-bold underline" onclick="openTermsModal(event)">Terms and Conditions</a> of the Islamic Society of Greater Houston.</label></div>
          </div>
          <button id="lti_submit_btn" class="btn-submit" type="button" onclick="submitMembership('lifetime_individual')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Complete Registration &amp; Pay $1,000.00</button>
          <p class="secure-note">🔒 Your payment information is secured with Stripe.</p>
        </div>
        <!-- /lifetimeIndividualSection -->


        <!-- ════════════════════════════════════════
             DEFAULT LOCKED SECTION
        ════════════════════════════════════════ -->
        <div id="defaultLockedSection" class="locked-section">
          <div class="lock-icon">
            <svg fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
            </svg>
          </div>
          <h3>Select a Membership Type to Continue</h3>
          <p>Personal information and payment fields will unlock once you choose a plan above.</p>
          <div class="locked-steps">
            <div class="locked-step"><div class="locked-step-num">2</div><div class="locked-step-bar"></div></div>
            <div class="locked-step"><div class="locked-step-num">3</div><div class="locked-step-bar"></div></div>
            <div class="locked-step"><div class="locked-step-num">4</div><div class="locked-step-bar"></div></div>
            <div class="locked-step"><div class="locked-step-num">5</div><div class="locked-step-bar"></div></div>
          </div>
        </div>
        <!-- /defaultLockedSection -->

      </div>
      <!-- /center-card -->


      <!-- ── RIGHT SIDE COLUMN ── -->
      <div class="membership-card-side">
        <div class="side-item">
          <img src="{{ asset('images/sadaqah.png') }}" alt="Sadaqah Jariyah">
          <h3>Sadaqah Jariyah</h3>
          <p>Invest in long-term projects like schools, water wells, and educational resources that benefit generations. Your contribution earns continuous reward long after it's given.</p>
        </div>
        <div class="side-item">
          <img src="{{ asset('images/latter.png') }}" alt="Zakat">
          <h3>Zakat</h3>
          <p>Zakat is one of the five pillars of Islam. Help us distribute Zakat to those who truly need it and fulfill your religious obligation through ISGH's trusted channels.</p>
        </div>
        <div class="side-item">
          <img src="{{ asset('images/mosque2.png') }}" alt="Masjid Maintenance Fund">
          <h3>Masjid Maintenance / Fund</h3>
          <p>Support daily operations, maintenance, and beautification of our facilities, ensuring a safe and welcoming environment for every worshiper.</p>
        </div>
        <div class="side-item">
          <img src="{{ asset('images/takecare.png') }}" alt="Ongoing Charity Fund">
          <h3>Ongoing Charity / Fund</h3>
          <p>Help sustain continuous charitable programs that serve our community throughout the year, from Ramadan initiatives to emergency aid distributions.</p>
        </div>
        <div class="side-item">
          <img src="{{ asset('images/matrimonial_services.png') }}" alt="Matrimonial Services">
          <h3>Matrimonial Services</h3>
          <p>Finding a life partner is a significant journey. ISGH is here to simplify that process and guide you toward a blessed and community-centered marriage.</p>
        </div>
      </div>

    </div>
    <!-- /membership-grid -->
  </div>
  <!-- /main-container -->


  <!-- ══════════ FOOTER (UNCHANGED) ══════════ -->
  <footer style="background:linear-gradient(135deg,#0a5e3a 0%,#0d7a4e 50%,#12a060 100%);border-radius:2rem 2rem 0 0;margin-top:5rem;border-top:10px solid white;border-left:2px solid white;border-right:2px solid white;box-shadow:0 -4px 24px rgba(180,220,255,0.25),inset 0 1px 0 rgba(255,255,255,0.15);">
    <div class="max-w-6xl mx-auto px-8 sm:px-12 py-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
      <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full border-2 border-yellow-500 flex items-center justify-center bg-green-900 flex-shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo">
          </div>
        </div>
        <p class="text-green-200 text-sm leading-relaxed" style="font-family:'SF Pro regular';">
          Islamic Society of Greater Houston – Building community through faith, education, and service.
        </p>
      </div>
      <div>
        <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Quick Links</h4>
        <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> About Us</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Prayer Times</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Events</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Programs</li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Services</h4>
        <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Shifa Clinics</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Quran Academy</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Chaplaincy</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Matrimonial</li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Contact</h4>
        <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Contact Us</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Donate</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Volunteer</li>
          <li class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer"><span class="text-green-400">•</span> Newsletter</li>
        </ul>
      </div>
    </div>
    <div class="border-t border-green-700/50 mx-8 sm:mx-12">
      <div class="max-w-6xl mx-auto py-5 flex flex-col sm:flex-row items-center justify-between gap-2" style="font-family:'SF Pro regular';">
        <p class="text-green-300 text-xs">© 2026 Islamic Society of Greater Houston. All rights reserved.</p>
        <p class="text-green-400 text-xs">Built with ❤️ for the Houston Muslim Community</p>
      </div>
    </div>
  </footer>

</div>

<!-- Mobile Menu Drawer -->
<div id="mobileMenu" class="fixed inset-0 z-[200] hidden">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeMobileMenu()"></div>
  <div class="absolute top-0 right-0 w-72 h-full bg-[#0d1f14] flex flex-col p-6 shadow-2xl overflow-y-auto">
    <button onclick="closeMobileMenu()" class="self-end text-white/70 hover:text-white mb-6">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <nav class="flex flex-col gap-1">
      <a href="{{ route('home') }}" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Home</a>
      <a href="#" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Centers</a>
      <a href="#" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Donate</a>
      <a href="{{ route('join') }}" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Become a Member</a>
      <a href="{{ route('membership-verification') }}" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Verify Membership</a>
    </nav>
    <div class="mt-6 flex flex-col gap-3">
      <a href="#" class="bg-white/20 text-white text-center px-6 py-2.5 rounded-full font-semibold">Sign in</a>
      <a href="{{ route('join') }}" style="background:#00d084;" class="text-white text-center px-6 py-2.5 rounded-full font-semibold">Join Now</a>
    </div>
  </div>
</div>
<script>
function openMobileMenu(){document.getElementById('mobileMenu').classList.remove('hidden');document.body.style.overflow='hidden';}
function closeMobileMenu(){document.getElementById('mobileMenu').classList.add('hidden');document.body.style.overflow='';}
</script>
</body>
</html>