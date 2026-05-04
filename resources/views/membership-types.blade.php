<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Choose Membership Type - ISGH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://unpkg.com/@zxing/library@0.19.1/umd/index.min.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
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

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --green: #0d7a55;
            --green-dark: #085c40;
            --green-mid: #10b981;
            --green-light: #e6f4ee;
            --bg: #f8f8f8;
        }

        html,
        body {
            height: 100%;
            font-family: 'SF Pro regular', 'DM Sans', sans-serif;
            background: var(--bg);
        }

        /* ─── NAVBAR ──────────────────────────────────── */
        .nav {
            border: 10px solid #ffff;
        }

        .navbar-glass {
            background: rgba(10, 10, 10, 0.88);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
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
            background-image: url('{{ asset('images/bussinesshandshake.png') }}');
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
            box-shadow: 0 4px 32px rgba(0, 0, 0, 0.07), 0 1px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid #f1f5f9;
        }

        /* Step-1 indicator sits at the very top of the card */
        .step-indicator:first-child {
            margin-bottom: 1.25rem;
        }

        /* ─── MEMBERSHIP DROPDOWN ─────────────────────── */
        .membership-selector {
            margin-bottom: 1.5rem;
            text-align: left;
            position: relative;
        }

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
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.08);
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

        .scan-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .scan-btn svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }

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

        .membership-banner.banner-checkomatic-active {
            display: block;
            min-height: 230px;
        }

        .banner-main-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .membership-banner::after {
            content: '';
            position: absolute;
            top: -25px;
            right: -25px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
            pointer-events: none;
        }

        .banner-left {
            flex: 1;
            min-width: 0;
            padding-right: 1rem;
        }

        .banner-title {
            font-family: 'SF Pro bold';
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
        }

        .banner-subtitle {
            font-size: 0.75rem;
            opacity: 0.85;
            margin-bottom: 0.6rem;
        }

        .banner-badges {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .banner-badge {
            background: rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            padding: 0.18rem 0.55rem;
            font-size: 0.65rem;
            font-family: 'SF Pro bold';
            white-space: nowrap;
        }

        .banner-price {
            text-align: right;
            flex-shrink: 0;
        }

        .membership-banner .checkomatic-amount-shell {
            display: none;
            position: relative;
            z-index: 1;
            margin-top: 1.15rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.22);
        }

        .membership-banner.banner-checkomatic-active .checkomatic-amount-shell {
            display: block;
        }

        .checkomatic-amount-label {
            display: block;
            font-family: 'SF Pro bold';
            font-size: 0.82rem;
            color: #fff6d8;
            margin-bottom: 0.5rem;
        }

        .checkomatic-amount-hint {
            font-family: 'SF Pro regular', sans-serif;
            font-size: 0.75rem;
            color: rgba(255, 247, 221, 0.85);
            margin-left: 0.25rem;
        }

        .checkomatic-amount-field {
            display: flex;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 0.55rem;
            background: rgba(255, 255, 255, 0.88);
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .checkomatic-amount-field:focus-within {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.08);
        }

        .checkomatic-amount-prefix {
            padding: 0.75rem 0.85rem;
            font-family: 'SF Pro bold';
            font-size: 1rem;
            color: #6b7280;
            background: rgba(255, 255, 255, 0.16);
            border-right: 1px solid rgba(17, 24, 39, 0.08);
            user-select: none;
        }

        .checkomatic-amount-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 0.75rem 0.85rem;
            font-family: 'SF Pro bold';
            font-size: 1rem;
            color: #111827;
            background: transparent;
            -moz-appearance: textfield;
        }

        .checkomatic-amount-input::-webkit-outer-spin-button,
        .checkomatic-amount-input::-webkit-inner-spin-button { -webkit-appearance: auto; }

        .checkomatic-amount-error {
            margin-top: 0.4rem;
            font-size: 0.92rem;
            color: #ffe1e1;
        }

        .checkomatic-amount-field.field-invalid {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220,38,38,0.1) !important;
        }

        .checkomatic-amount-note {
            margin-top: 0.45rem;
            font-size: 0.78rem;
            color: #fff8e7;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .checkomatic-amount-note::before {
            content: '';
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 3px;
            background: #10b981;
            flex-shrink: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M2 6l3 3 5-5' stroke='white' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-size: 10px;
            background-repeat: no-repeat;
            background-position: center;
        }

        .checkomatic-warning {
            display: none;
            margin-top: -1rem;
            margin-bottom: 1.5rem;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #fca5a5;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .checkomatic-warning p + p {
            margin-top: 0.3rem;
        }

        .dependent-address-summary {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.9rem 1rem;
            text-align: left;
        }

        .dependent-address-summary-label {
            display: block;
            font-family: 'SF Pro bold';
            font-size: 0.72rem;
            color: #374151;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .dependent-address-summary-value {
            font-size: 0.84rem;
            color: #111827;
            line-height: 1.5;
        }

        .dependent-address-fields {
            display: none;
        }

        .banner-price .price-amount {
            font-family: 'SF Pro bold';
            font-size: 1.75rem;
            line-height: 1;
        }

        .banner-price .price-period {
            font-size: 0.65rem;
            opacity: 0.75;
            margin-top: 0.15rem;
        }

        /* Banner color variants */
        .banner-family,
        .banner-individual,
        .banner-flat,
        .banner-checkomatic,
        .banner-lifetime {
            background:
              radial-gradient(ellipse at 72% 48%, rgba(200, 160, 40, 0.35) 0%, transparent 55%),
              linear-gradient(130deg, #4a3008 0%, #6e4c0c 30%, #8f6a0a 58%, #9e7a0e 78%, #7a5a0c 100%);
            box-shadow: 0 6px 24px rgba(80, 55, 10, 0.28);
        }

        /* ─── FORM FIELDS ─────────────────────────────── */
        .fields-stack {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

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

        .field label span {
            color: #ef4444;
        }

        .field input,
        .field select {
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

        .field input:focus,
        .field select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.08);
        }
        .field input.field-invalid,
        .field select.field-invalid {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
        }
        .field input.field-valid,
        .field select.field-valid {
            border-color: #10b981 !important;
        }

        .field input::placeholder {
            color: #c0c8d4;
            font-size: 0.83rem;
        }

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

        .dependent-card-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 0.85rem;
        }

        .dependent-card-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }

        .dependent-card-actions {
            display: flex;
            justify-content: flex-end;
        }

        .dependent-scan-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 1px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            color: #eaf7f3;
            cursor: pointer;
            background: linear-gradient(90deg,#0f5c45 0%,#2f8f6b 50%,#55c59a 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            justify-self: center;
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

        .scan-btn-sm:hover {
            background: #059669;
        }

        .scan-btn-sm svg {
            width: 12px;
            height: 12px;
        }

        .btn-add-member {
            width: 100%;
            padding: 0.8rem;
            border: 2px dashed #059669;
            border-radius: 0.9rem;
            color: #ffffff;
            font-family: 'SF Pro bold';
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 1.25rem;
            background: #059669;
        }

        .btn-add-member:hover {
            border-color: #047857;
            color: #ffffff;
            background: #047857;
        }

        /* ─── ORDER SUMMARY ───────────────────────────── */
        .order-summary {
            background: linear-gradient(135deg, #0a4f32 0%, #0d7a52 60%, #0f9460 100%);
            border-radius: 1rem;
            padding: 1.5rem 1.6rem 1.6rem;
            color: white;
            margin-top: 1.5rem;
            box-shadow: 0 4px 24px rgba(10, 79, 50, 0.25);
            position: relative;
            overflow: hidden;
        }

        .order-summary-bg-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            mix-blend-mode: screen;
            opacity: 0.18;
            pointer-events: none;
            z-index: 0;
        }

        .order-summary-rings {
            position: absolute;
            top: -10px;
            left: -10px;
            width: 120px;
            opacity: 0.15;
            pointer-events: none;
            z-index: 0;
        }

        .order-summary-inner-title {
            font-family: 'SF Pro bold';
            font-size: 1.25rem;
            color: white;
            margin-bottom: 1.1rem;
            letter-spacing: 0.01em;
        }

        .order-summary-header {
            display: none;
        }

        .order-badge {
            display: none;
        }

        .order-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.82);
            padding: 0.55rem 0;
            position: relative;
            z-index: 1;
        }

        .order-row+.order-row {
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .order-row span:last-child {
            font-family: 'SF Pro bold';
            color: white;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.7rem;
            padding-top: 0.9rem;
            border-top: 1px solid rgba(255, 255, 255, 0.22);
            position: relative;
            z-index: 1;
        }

        .order-total-label {
            font-family: 'SF Pro bold';
            font-size: 1rem;
            color: white;
        }

        .order-total-amount {
            font-family: 'SF Pro bold';
            font-size: 1.35rem;
            color: white;
            letter-spacing: 0.01em;
        }

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

        .checkbox-box:hover {
            border-color: #10b981;
            background: #f6fdfb;
        }

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
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s, opacity 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-submit:hover:not(:disabled) {
            background: #033020;
            transform: translateY(-1px);
            box-shadow: 0 5px 16px rgba(4, 61, 39, 0.2);
        }

        .btn-submit:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            opacity: 0.65;
            transform: none;
            box-shadow: none;
        }

        .btn-submit svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* ─── CONFIRM POPUP ──────────────────────────────── */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(3px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }
        .confirm-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        .confirm-box {
            background: white;
            border-radius: 1.1rem;
            padding: 2rem 2.2rem 1.6rem;
            max-width: 380px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.22);
            transform: scale(0.94);
            transition: transform 0.2s;
        }
        .confirm-overlay.active .confirm-box { transform: scale(1); }
        .confirm-icon {
            width: 52px; height: 52px;
            background: #e6f4ee;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
        .confirm-icon svg { width: 26px; height: 26px; color: #0d7a55; }
        .confirm-title {
            font-family: 'SF Pro bold';
            font-size: 1.1rem;
            color: #111;
            margin-bottom: 0.5rem;
        }
        .confirm-body {
            font-size: 0.82rem;
            color: #6b7280;
            line-height: 1.55;
            margin-bottom: 1.5rem;
        }
        .confirm-actions {
            display: flex;
            gap: 0.75rem;
        }
        .confirm-btn-no {
            flex: 1;
            padding: 0.7rem;
            border-radius: 999px;
            border: 1.5px solid #d1d5db;
            background: white;
            font-family: 'SF Pro bold';
            font-size: 0.84rem;
            color: #374151;
            cursor: pointer;
            transition: background 0.15s;
        }
        .confirm-btn-no:hover { background: #f3f4f6; }
        .confirm-btn-yes {
            flex: 1;
            padding: 0.7rem;
            border-radius: 999px;
            border: none;
            background: #043d27;
            font-family: 'SF Pro bold';
            font-size: 0.84rem;
            color: white;
            cursor: pointer;
            transition: background 0.15s;
        }
        .confirm-btn-yes:hover { background: #033020; }
        .checkomatic-spouse-note {
            font-size: 0.88rem;
            line-height: 1.55;
            color: #374151;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.9rem;
            padding: 0.95rem 1rem;
            text-align: left;
            margin-top: 0.9rem;
        }

        .secure-note {
            font-size: 0.66rem;
            color: #c4c9d4;
            text-align: center;
            margin-top: 0.6rem;
        }

        /* ─── STRIPE CARD ELEMENT ───────────────────────── */
        .stripe-card-section {
            margin-bottom: 1.25rem;
        }
        .stripe-card-label {
            font-family: 'SF Pro bold', sans-serif;
            font-size: 0.82rem;
            color: #374151;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .stripe-card-label svg { color: #10b981; flex-shrink: 0; }
        .stripe-card-name-field {
            margin-bottom: 0.85rem;
        }
        #stripe-card-element {
            border: 1px solid #e2e8f0;
            border-radius: 0.65rem;
            padding: 0.85rem 0.95rem;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        #stripe-card-element.StripeElement--focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.08);
        }
        #stripe-card-element.StripeElement--invalid {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        #stripe-card-error {
            color: #dc2626;
            font-size: 0.78rem;
            margin-top: 0.4rem;
            min-height: 1.1em;
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .lock-icon svg {
            width: 24px;
            height: 24px;
            color: #10b981;
        }

        .locked-section h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #0d7a55;
            margin-bottom: 0.4rem;
            font-family: 'SF Pro bold';
        }

        .locked-section p {
            font-size: 0.8rem;
            color: #0d7a55;
            line-height: 1.5;
        }

        .locked-steps {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .locked-step {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(16, 185, 129, 0.05);
            border-radius: 0.6rem;
            padding: 0.6rem 0.9rem;
            opacity: 0.45;
        }

        .locked-step-num {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #d1fae5;
            color: #0d7a55;
            font-size: 0.7rem;
            font-family: 'SF Pro bold';
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .locked-step-bar {
            height: 7px;
            flex: 1;
            background: #d1fae5;
            border-radius: 999px;
        }

        /* ─── SECTION DIVIDER ─────────────────────────── */
        .section-divider {
            height: 1px;
            background: #f1f3f5;
            margin: 1.75rem 0;
        }

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

            .side-item img {
                max-width: 120px;
                height: 120px;
            }
        }

        @media (max-width: 900px) {
            .membership-grid {
                grid-template-columns: 1fr;
            }

            .membership-card-side {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
            }

            .side-item {
                flex: 0 0 calc(50% - 1rem);
                margin-bottom: 0;
            }

            .side-item img {
                height: 130px;
            }
        }

        @media (max-width: 560px) {
            .center-card {
                padding: 1.75rem 1.25rem 2rem;
            }

            .banner-price .price-amount {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 500px) {
            .side-item {
                flex: 0 0 100%;
            }

            .main-container {
                padding: 0 1rem 4rem;
            }

            .center-card {
                padding: 1.5rem 1rem 2rem;
            }
        }

        .order-row {
            flex-wrap: wrap;
        }

        /* ─── SPOUSE / MEMBER BLOCKS ───────────────────── */
        .spouse-block {
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            padding: 1rem 1.1rem 0.5rem;
            margin-bottom: 1rem;
            background: #fafafa;
        }

        .spouse-block-header {
            margin-bottom: 0.75rem;
        }

        .btn-remove-block {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            width: 2.6rem;
            height: 2.6rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem;
            border-radius: 999px;
            transition: background 0.15s, transform 0.15s;
            justify-self: end;
        }

        .btn-remove-block:hover {
            background: #fee2e2;
            transform: translateY(-1px);
        }

        .btn-remove-block svg {
            width: 1.15rem;
            height: 1.15rem;
        }

        @media (max-width: 640px) {
            .dependent-card-header {
                grid-template-columns: 1fr;
                justify-items: stretch;
            }

            .dependent-scan-btn {
                width: 100%;
            }

            .dependent-card-actions {
                justify-content: flex-end;
            }
        }

        /* ─── MOBILE (≤ 768px) ────────────────────────── */
        /* ─── MOBILE HORIZONTAL SCROLL SECTION ───────── */
        .mobile-side-scroll {
            display: none; /* hidden on desktop */
        }

        .mobile-scroll-item {
            flex: 0 0 155px;
            text-align: center;
            background: white;
            border-radius: 1rem;
            padding: 1rem 0.75rem 1.1rem;
            box-shadow: 0 2px 14px rgba(0,0,0,0.07);
            border: 1px solid #f1f5f9;
            scroll-snap-align: start;
        }

        .mobile-scroll-item img {
            width: 100%;
            height: 90px;
            object-fit: contain;
            margin-bottom: 0.55rem;
        }

        .mobile-scroll-item h3 {
            font-family: 'SF Pro bold';
            font-size: 0.78rem;
            color: #0d7a55;
            margin-bottom: 0.25rem;
        }

        .mobile-scroll-item p {
            font-size: 0.63rem;
            color: #6b7280;
            line-height: 1.45;
        }

        @media (max-width: 768px) {
            /* Remove outer white frame/border on mobile */
            .page-outer-wrapper {
                border: none !important;
                border-radius: 0 !important;
            }

            /* Reduce hero upward shift so "Join ISGH" clears the navbar */
            .hero-bg {
                top: -25px !important;
            }

            /* Single-column grid, center card only — both side columns hidden */
            .membership-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .membership-grid > .membership-card-side {
                display: none;
            }

            /* Main container on mobile */
            .main-container {
                margin-top: -2rem;
                padding: 0 0.75rem 2rem;
            }

            /* Show the mobile horizontal scroll section */
            .mobile-side-scroll {
                display: flex;
                overflow-x: auto;
                gap: 0.85rem;
                padding: 1.5rem 0.75rem 1.75rem;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                scroll-snap-type: x mandatory;
            }

            .mobile-side-scroll::-webkit-scrollbar {
                display: none;
            }

            /* Center card: proper radius and padding */
            .center-card {
                border-radius: 1.25rem;
                padding: 1.5rem 1rem 2rem;
            }

            /* Membership banner: stack price below title on very small screens */
            .membership-banner {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
                min-height: auto;
                padding: 1rem 1.1rem;
            }

            .banner-main-row {
                flex-direction: column;
                gap: 0.5rem;
            }

            .banner-price {
                text-align: left;
            }

            .banner-price .price-amount {
                font-size: 1.3rem;
            }

            /* Form step indicator */
            .step-number {
                width: 42px;
                height: 42px;
                font-size: 1rem;
            }

            .form-section-title {
                font-size: 1.2rem;
            }

            /* Make locked steps bar full width */
            .locked-steps {
                gap: 0.35rem;
            }
        }

        /* Submission overlay */
        .submit-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
            backdrop-filter: blur(3px);
        }

        .submit-overlay.visible {
            display: flex;
        }

        .submit-spinner {
            background: white;
            border-radius: 1.5rem;
            padding: 2.5rem 3rem;
            text-align: center;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.25);
            min-width: 300px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spin-icon {
            width: 52px;
            height: 52px;
            border: 3px solid #e5e7eb;
            border-top-color: var(--green);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            margin: 0 auto;
        }

        .overlay-step {
            margin-top: 1.1rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem;
            color: #111827;
            font-weight: 600;
        }

        .overlay-sub {
            margin-top: 0.3rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.78rem;
            color: #9ca3af;
        }

        .overlay-timer {
            display: inline-block;
            margin-top: 1rem;
            background: #f3f4f6;
            border-radius: 999px;
            padding: 0.3rem 1rem;
            font-family: 'SF Pro bold', monospace;
            font-size: 0.85rem;
            color: #6b7280;
            letter-spacing: 0.04em;
        }

        .overlay-steps-track {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-top: 1.2rem;
        }

        .overlay-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e5e7eb;
            transition: background 0.3s;
        }

        .overlay-dot.active {
            background: var(--green);
        }

        .overlay-dot.done {
            background: #10b981;
        }

        /* ZIP / phone validation feedback */
        .zip-msg,
        .phone-msg,
        .email-msg {
            font-size: 12px;
            margin-top: 4px;
            min-height: 16px;
        }

        .zip-msg.error,
        .phone-msg.error,
        .email-msg.error {
            color: #dc2626;
        }

        .zip-msg.success,
        .phone-msg.success,
        .email-msg.success {
            color: #10b981;
        }

        .zone-field {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .zone-field label {
            color: #166534;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .zone-field select {
            width: 100%;
            margin-top: 6px;
            padding: 7px 10px;
            border: 1px solid #86efac;
            border-radius: 6px;
            background: #fff;
            font-size: 14px;
        }

        .zone-field .zone-text {
            font-size: 14px;
            font-weight: 600;
            color: #15803d;
            margin-top: 6px;
            padding: 6px 10px;
            background: #dcfce7;
            border-radius: 6px;
            display: inline-block;
        }

        /* ── ID SCAN MODAL ─────────────────────────────────────────────────── */
        .id-scan-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .id-scan-overlay.active { display: flex; }

        .id-scan-modal {
            background: #0f1923;
            border-radius: 1.25rem;
            width: min(95vw, 520px);
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
        }

        .id-scan-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            background: linear-gradient(90deg,#0f5c45,#2f8f6b);
        }
        .id-scan-header h3 {
            color: #eaf7f3;
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }
        .id-scan-close-btn {
            background: rgba(255,255,255,0.15);
            border: none;
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .id-scan-close-btn:hover { background: rgba(255,255,255,0.3); }

        .id-scan-body { padding: 1.25rem; }

        .id-scan-instruction {
            text-align: center;
            color: #9ca3af;
            font-size: 0.82rem;
            margin-bottom: 0.9rem;
            line-height: 1.5;
        }
        .id-scan-instruction strong { color: #eaf7f3; }

        .id-scan-viewfinder {
            position: relative;
            background: #000;
            border-radius: 0.75rem;
            overflow: hidden;
            aspect-ratio: 4/3;
        }
        .id-scan-viewfinder video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .id-scan-corners {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }
        .id-scan-corners::before,
        .id-scan-corners::after,
        .id-scan-corner-br,
        .id-scan-corner-bl {
            content: '';
            position: absolute;
            width: 28px;
            height: 28px;
            border-color: #10b981;
            border-style: solid;
        }
        .id-scan-corners::before { top: 12px; left: 12px; border-width: 3px 0 0 3px; border-radius: 4px 0 0 0; }
        .id-scan-corners::after  { top: 12px; right: 12px; border-width: 3px 3px 0 0; border-radius: 0 4px 0 0; }
        .id-scan-corner-br { bottom: 12px; right: 12px; border-width: 0 3px 3px 0; border-radius: 0 0 4px 0; }
        .id-scan-corner-bl { bottom: 12px; left: 12px;  border-width: 0 0 3px 3px; border-radius: 0 0 0 4px; }

        .id-scan-laser {
            position: absolute;
            left: 10%; right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #10b981, transparent);
            animation: idLaserSweep 1.8s ease-in-out infinite;
            border-radius: 2px;
        }
        @keyframes idLaserSweep {
            0%   { top: 15%; opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { top: 85%; opacity: 0; }
        }

        .id-scan-status {
            text-align: center;
            margin-top: 0.85rem;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
        }
        .id-scan-status.scanning { color: #9ca3af; background: #1a2430; }
        .id-scan-status.success  { color: #10b981; background: #052e1c; }
        .id-scan-status.error    { color: #f87171; background: #2d1515; }

        .id-scan-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        .id-scan-cancel-btn {
            flex: 1;
            padding: 0.7rem;
            border: 1px solid #374151;
            background: transparent;
            color: #9ca3af;
            border-radius: 0.7rem;
            cursor: pointer;
            font-size: 0.85rem;
            font-family: inherit;
        }
        .id-scan-cancel-btn:hover { background: #1f2937; color: #e5e7eb; }
    </style>

    <script>
        // ─── CURRENT TYPE & CONFIG ────────────────────────────────────────────────
        let currentMembershipType = '';

        const MEMBERSHIP_CONFIG = {
            family: {
                bannerTitle: 'Family Membership (Primary & Spouse)',
                bannerSubtitle: 'Includes both primary member and spouse',
                priceAmount: '$40',
                pricePeriod: 'Per Year',
                hasSpouse: true,
                hasFlatMembers: false,
                isAnnual: true,
                orderType: 'FAMILY MEMBERSHIP',
                orderFee: '$40.00',
                orderTotal: '$40.00',
                billingNote: '',
                submitLabel: 'Complete Registration &amp; Pay',
            },
            individual: {
                bannerTitle: 'Individual Membership',
                bannerSubtitle: 'Individual membership only',
                priceAmount: '$25',
                pricePeriod: 'Per Year',
                hasSpouse: false,
                hasFlatMembers: false,
                isAnnual: true,
                orderType: 'INDIVIDUAL MEMBERSHIP',
                orderFee: '$25.00',
                orderTotal: '$25.00',
                billingNote: '',
                submitLabel: 'Complete Registration &amp; Pay',
            },
            flat: {
                bannerTitle: 'Individual Membership',
                bannerSubtitle: 'All household members for one flat rate',
                priceAmount: '$20',
                pricePeriod: 'Per Year',
                hasSpouse: false,
                hasFlatMembers: true,
                isAnnual: true,
                orderType: 'INDIVIDUAL MEMBERSHIP',
                orderFee: '$20.00',
                orderTotal: '$20.00',
                billingNote: '',
                submitLabel: 'Complete Registration &amp; Pay',
            },
            checkomatic_family: {
                bannerTitle: 'Checkomatic Membership',
                bannerSubtitle: 'Bundle (up to 2 members). This fund only apply for all the funds except Zakat & Sadaqah for needy.',
                priceAmount: '$40',
                pricePeriod: 'Per Person/mo',
                hasSpouse: true,
                spouseOptional: true,
                downgradeTo: 'checkomatic_individual',
                hasFlatMembers: false,
                orderType: 'CHECKOMATIC FAMILY',
                orderFee: '$40.00 / month',
                orderTotal: '$40.00',
                billingNote: '',
                submitLabel: 'Complete Registration &amp; Pay',
            },
            checkomatic_individual: {
                bannerTitle: 'Checkomatic Membership',
                bannerSubtitle: 'Bundle (up to 2 members). This fund only apply for all the funds except Zakat & Sadaqah for needy.',
                priceAmount: '$20',
                pricePeriod: 'Per Person/mo',
                hasSpouse: false,
                spouseOptional: true,
                upgradesTo: 'checkomatic_family',
                hasFlatMembers: false,
                orderType: 'CHECKOMATIC INDIVIDUAL',
                orderFee: '$20.00 / month',
                orderTotal: '$20.00',
                billingNote: 'Monthly billing — cancel anytime',
                submitLabel: 'Complete Registration &amp; Pay',
            },
            lifetime_family: {
                bannerTitle: 'Lifetime Membership',
                bannerSubtitle: 'Lifetime Membership for Family only',
                priceAmount: '$1500',
                pricePeriod: 'LIFETIME Unlimited',
                hasSpouse: true,
                spouseOptional: true,
                downgradeTo: 'lifetime_individual',
                hasFlatMembers: false,
                orderType: 'LIFETIME FAMILY',
                orderFee: '$1,500.00 / Lifetime',
                orderTotal: '$1,500.00',
                billingNote: '',
                submitLabel: 'Complete Registration &amp; Pay',
            },
            lifetime_individual: {
                bannerTitle: 'Lifetime Membership (Individual)',
                bannerSubtitle: 'Lifetime Membership for Individual only',
                priceAmount: '$1000',
                pricePeriod: 'LIFETIME Unlimited',
                hasSpouse: false,
                spouseOptional: true,
                upgradesTo: 'lifetime_family',
                hasFlatMembers: false,
                orderType: 'LIFETIME INDIVIDUAL',
                orderFee: '$1,000.00 / Lifetime',
                orderTotal: '$1,000.00',
                billingNote: '',
                submitLabel: 'Complete Registration &amp; Pay',
            },
        };

        // ─── SECTION TOGGLE ──────────────────────────────────────────────────────
        function toggleMembershipForm() {
            const val = document.getElementById("membershipSelector").value;
            ['defaultLockedSection', 'unifiedMembershipSection'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

            if (!val) {
                document.getElementById('defaultLockedSection').style.display = 'block';
                currentMembershipType = '';
                return;
            }

            currentMembershipType = val;
            const unified = document.getElementById('unifiedMembershipSection');
            unified.style.display = 'block';
            // Reset confirm/continue state when membership type changes
            document.getElementById('uni_confirm_wrap').style.display = 'block';
            document.getElementById('uni_rest_of_form').style.display = 'none';
            document.getElementById('uni_duplicate_error').style.display = 'none';
            updateConfirmBtn();
            updateUnifiedForm(val);
            attachFieldFeedback(unified);
            attachReadinessListeners();
            attachConfirmWatchers();
            checkFormReadiness();
            initStripeCardElement();
        }

        function updateUnifiedForm(type) {
            const cfg = MEMBERSHIP_CONFIG[type];
            if (!cfg) return;

            const setText = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.textContent = text;
            };
            const setFeeLabel = (text) => {
                const el = document.getElementById('uni_order_fee_label');
                if (el) el.textContent = text;
            };
            setText('uni_banner_title', cfg.bannerTitle);
            setText('uni_banner_subtitle', cfg.bannerSubtitle);
            setText('uni_price_amount', cfg.priceAmount);
            setText('uni_price_period', cfg.pricePeriod);
            setText('uni_checkomatic_warning_amount', cfg.priceAmount);
            const banner = document.getElementById('uni_banner');

            const spouseSection = document.getElementById('uni_spouse_section');
            const addSpouseBtn = document.getElementById('uni_add_spouse_btn');
            const spouseFormWrap = document.getElementById('uni_spouse_form_wrap');
            const flatSection = document.getElementById('uni_flat_members_section');
            const flatCountRow = document.getElementById('uni_flat_count_row');
            const feeRow = document.getElementById('uni_order_fee_row');
            if (cfg.hasSpouse || cfg.spouseOptional) {
                if (spouseSection) spouseSection.style.display = 'block';
                if (cfg.spouseOptional) {
                    if (addSpouseBtn) addSpouseBtn.style.display = '';
                    if (spouseFormWrap) spouseFormWrap.style.display = 'none';
                    _clearSpouseBlock0();
                } else {
                    if (addSpouseBtn) addSpouseBtn.style.display = 'none';
                    if (spouseFormWrap) spouseFormWrap.style.display = '';
                }
            } else {
                if (spouseSection)   spouseSection.style.display   = 'none';
                if (spouseFormWrap)  spouseFormWrap.style.display  = 'none';
            }
            if (flatSection) flatSection.style.display = cfg.hasFlatMembers ? 'block' : 'none';
            if (flatCountRow) flatCountRow.style.display = cfg.hasFlatMembers ? 'flex' : 'none';
            if (feeRow) feeRow.style.display = cfg.hasFlatMembers ? 'none' : 'flex';

            setText('uni_order_type', cfg.orderType);
            setText('uni_order_fee', cfg.orderFee);
            setFeeLabel((type === 'checkomatic_family' || type === 'checkomatic_individual') ? 'Donation Amount' : 'Membership Fee');
            if (cfg.hasFlatMembers) {
                updateFlatTotal();
            } else {
                setText('uni_order_total', cfg.orderTotal);
            }

            const noteEl = document.getElementById('uni_billing_note');
            if (noteEl) {
                noteEl.textContent = cfg.billingNote;
                noteEl.style.display = cfg.billingNote ? '' : 'none';
            }

            const autoRenewalBox = document.getElementById('uni_auto_renewal_box');
            if (autoRenewalBox) {
                autoRenewalBox.style.display = cfg.isAnnual ? '' : 'none';
                if (!cfg.isAnnual) {
                    const cb = document.getElementById('uni_auto_renewal');
                    if (cb) cb.checked = false;
                }
            }

            const isCheckomatic = (type === 'checkomatic_family' || type === 'checkomatic_individual');
            const amountWrap  = document.getElementById('uni_checkomatic_amount_wrap');
            const amountInput = document.getElementById('uni_checkomatic_amount');
            const warningBox = document.getElementById('uni_checkomatic_warning');
            const donationTypeField = document.getElementById('uni_donation_type_field');
            if (banner) banner.classList.toggle('banner-checkomatic-active', isCheckomatic);
            if (amountWrap) amountWrap.style.display = isCheckomatic ? '' : 'none';
            if (donationTypeField) donationTypeField.style.display = isCheckomatic ? '' : 'none';
            const _donationSelect = document.getElementById('uni_donation_type');
            if (!isCheckomatic) {
                // Reset to empty when leaving checkomatic
                if (_donationSelect) _donationSelect.value = '';
            } else {
                // Re-apply cached donation types from the last ZIP lookup (if any)
                const _cachedTypes = _zipState['uni']?.donationTypes;
                if (_cachedTypes && _cachedTypes.length > 0) _populateDonationTypes(_cachedTypes);
            }
            if (warningBox && !isCheckomatic) warningBox.style.display = 'none';
            if (amountInput && isCheckomatic) {
                const minVal = getCheckomaticMinimum(type);
                amountInput.min = minVal;
                amountInput.removeAttribute('max');
                amountInput.value = Math.max(parseInt(amountInput.value, 10) || minVal, minVal);
                setText('uni_checkomatic_warning_amount', '$' + minVal);
                setText('uni_checkomatic_amount_hint', `(minimum $${minVal}/month)`);
                setText('uni_price_amount', '$' + amountInput.value);
                updateCheckomaticNote();
            } else if (!cfg.hasFlatMembers) {
                const feeAmount = parseFloat(String(cfg.orderTotal).replace(/[^0-9.]/g, '')) || 0;
                const feeSuffix = cfg.pricePeriod === 'Per Month' ? ' / month' : '';
                setText('uni_order_fee', formatUsd(feeAmount) + feeSuffix);
                setText('uni_order_total', formatUsd(feeAmount));
            }

            const submitLabel = document.getElementById('uni_submit_label');
            if (submitLabel) submitLabel.innerHTML = cfg.submitLabel;

            if (cfg.hasSpouse && !cfg.spouseOptional) autoFillSpouseAddresses();
            if (cfg.hasFlatMembers) autoFillFlatMemberAddresses();

            loadCitiesFor('uni');
            if (cfg.hasSpouse && !cfg.spouseOptional) loadCitiesFor('uni_spouse_0');
        }

        function getCheckomaticMinimum(type = currentMembershipType) {
            return 10;
        }

        function formatUsd(amount) {
            return '$' + Number(amount || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function syncCheckomaticOrderSummary(amount) {
            const feeEl = document.getElementById('uni_order_fee');
            const totalEl = document.getElementById('uni_order_total');
            const formatted = formatUsd(amount);
            if (feeEl) feeEl.textContent = `${formatted} / month`;
            if (totalEl) totalEl.textContent = formatted;
        }

        function syncCheckomaticWarningVisibility() {
            const warningBox = document.getElementById('uni_checkomatic_warning');
            if (!warningBox) return;
            warningBox.style.display = 'block';
        }

        function updateCheckomaticNote() {
            const input   = document.getElementById('uni_checkomatic_amount');
            const note    = document.getElementById('uni_checkomatic_note');
            const errEl   = document.getElementById('uni_checkomatic_error');
            const field   = input?.closest('.checkomatic-amount-field');
            if (!input || !note) return;
            const val = parseFloat(input.value);
            const min = parseFloat(input.min) || 10;
            const priceEl = document.getElementById('uni_price_amount');
            if (isNaN(val) || val < min) {
                if (errEl) { errEl.textContent = `Minimum monthly amount is $${min.toFixed(2)}.`; errEl.style.display = ''; }
                if (field)  field.classList.add('field-invalid');
                note.style.display = 'none';
                if (priceEl) priceEl.textContent = '$' + Math.round(min);
                syncCheckomaticOrderSummary(min);
            } else {
                if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
                if (field)  field.classList.remove('field-invalid');
                note.textContent  = `You will be charged $${val.toFixed(2)}/month`;
                note.style.display = '';
                if (priceEl) priceEl.textContent = '$' + Math.round(val);
                syncCheckomaticOrderSummary(val);
            }
            syncCheckomaticWarningVisibility();
        }

        function buildDependentAddressText(street, city, state, zip, zone) {
            const lineOne = (street || '').trim();
            const locality = [city, state].map(v => (v || '').trim()).filter(Boolean).join(', ');
            const lineTwo = [locality, (zip || '').trim()].filter(Boolean).join(' ');
            const addressText = [lineOne, lineTwo].filter(Boolean).join(', ') || 'Same as primary member address';
            const zoneText = (zone || '').trim() ? ` • 📍 ${zone}` : '';
            return addressText + zoneText;
        }

        function setDependentAddressSummary(summaryId, street, city, state, zip, zone) {
            const summaryEl = document.getElementById(summaryId);
            if (!summaryEl) return;
            summaryEl.textContent = buildDependentAddressText(street, city, state, zip, zone);
        }

        // ─── DYNAMIC SPOUSE BLOCKS ───────────────────────────────────────────────

        function _clearSpouseBlock0() {
            const block = document.getElementById('uni_spouse_block_0');
            if (!block) return;
            block.querySelectorAll('input').forEach(el => el.value = '');
            block.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
        }

        function showSpouseForm() {
            const cfg = MEMBERSHIP_CONFIG[currentMembershipType];
            if (currentMembershipType === 'checkomatic_family' || currentMembershipType === 'checkomatic_individual') {
                openCheckomaticSpouseDisclaimer();
                return;
            }
            if (cfg?.upgradesTo) {
                currentMembershipType = cfg.upgradesTo;
            }
            updateUnifiedForm(currentMembershipType);
            const btn  = document.getElementById('uni_add_spouse_btn');
            const wrap = document.getElementById('uni_spouse_form_wrap');
            if (btn)  btn.style.display  = 'none';
            if (wrap) wrap.style.display = '';
            loadCitiesFor('uni_spouse_0', () => {
                autoFillSpouseAddresses();
                document.getElementById('uni_spouse_0_city')?.setAttribute('disabled', 'disabled');
            });
            autoFillSpouseAddresses();
            attachFieldFeedback(wrap);
            attachReadinessListeners();
            checkFormReadiness();
        }

        function openCheckomaticSpouseDisclaimer() {
            const modal = document.getElementById('checkomaticSpouseModal');
            if (modal) modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCheckomaticSpouseDisclaimer() {
            const modal = document.getElementById('checkomaticSpouseModal');
            if (modal) modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function confirmCheckomaticSpouseDisclaimer() {
            closeCheckomaticSpouseDisclaimer();
            const cfg = MEMBERSHIP_CONFIG[currentMembershipType];
            if (cfg?.upgradesTo) {
                currentMembershipType = cfg.upgradesTo;
            }
            updateUnifiedForm(currentMembershipType);
            const btn  = document.getElementById('uni_add_spouse_btn');
            const wrap = document.getElementById('uni_spouse_form_wrap');
            if (btn)  btn.style.display  = 'none';
            if (wrap) wrap.style.display = '';
            loadCitiesFor('uni_spouse_0', () => {
                autoFillSpouseAddresses();
                document.getElementById('uni_spouse_0_city')?.setAttribute('disabled', 'disabled');
            });
            autoFillSpouseAddresses();
            attachFieldFeedback(wrap);
            attachReadinessListeners();
            checkFormReadiness();
        }

        function removeSpouseForm() {
            const cfg = MEMBERSHIP_CONFIG[currentMembershipType];
            if (cfg?.downgradeTo) {
                currentMembershipType = cfg.downgradeTo;
            }
            updateUnifiedForm(currentMembershipType);
            checkFormReadiness();
        }

        let spouseCount = 1;

        function addSpouseBlock() {
            const container = document.getElementById('uni_spouses_container');
            if (!container) return;
            const idx = spouseCount++;
            const block = document.createElement('div');
            block.className = 'spouse-block';
            block.id = 'uni_spouse_block_' + idx;
            block.innerHTML = `
        <div class="spouse-block-header dependent-card-header">
          <div class="dependent-card-meta">
            <div class="member-tag">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Spouse ${idx + 1}
            </div>
          </div>
          <div class="dependent-scan-btn" onclick="openIdScan('uni_spouse_${idx}')">
            <svg viewBox="0 0 24 24" fill="none" stroke="#eaf7f3" stroke-width="2.6" style="width:26px;height:26px;"><path d="M8 4H6a2 2 0 0 0-2 2v2" stroke-linecap="round"/><path d="M16 4h2a2 2 0 0 1 2 2v2" stroke-linecap="round"/><path d="M8 20H6a2 2 0 0 1-2-2v-2" stroke-linecap="round"/><path d="M16 20h2a2 2 0 0 0 2-2v-2" stroke-linecap="round"/></svg>
            <span style="letter-spacing:0.3px;">Scan Your ID</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="#f7c873" stroke-width="2.4" style="width:22px;height:22px;"><path d="M12 3l2.2 5.2L20 10l-5.8 1.8L12 17l-2.2-5.2L4 10l5.8-1.8L12 3z" stroke-linejoin="round"/></svg>
          </div>
          <div class="dependent-card-actions">
            <button type="button" class="btn-remove-block" onclick="removeBlock('uni_spouse_block_${idx}')" title="Remove Spouse">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </div>
        </div>
        <div class="fields-stack">
          <div class="field"><label>Email Address</label><input type="email" id="uni_spouse_${idx}_email" placeholder="spouse@example.com"><div id="uni_spouse_${idx}_email_msg" class="email-msg"></div></div>
          <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="uni_spouse_${idx}_phone" placeholder="e.g. (832) 555-0100" oninput="restrictPhoneInput(this)" onblur="validateUsPhone(this)"><div id="uni_spouse_${idx}_phone_msg" class="phone-msg"></div></div>
          <div class="field"><label>First Name <span>*</span></label><input type="text" id="uni_spouse_${idx}_first_name" placeholder="First Name"></div>
          <div class="field"><label>Middle Name</label><input type="text" id="uni_spouse_${idx}_middle_name" placeholder="Middle Name (Optional)"></div>
          <div class="field"><label>Last Name <span>*</span></label><input type="text" id="uni_spouse_${idx}_last_name" placeholder="Last Name"></div>
          <div class="field"><label>Date of Birth <span>*</span></label><input type="text" id="uni_spouse_${idx}_dob" placeholder="MM/DD/YYYY"><div class="dob-msg"></div></div>
          <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" id="uni_spouse_${idx}_txdl" placeholder="e.g. TX7234578"></div>
          <div class="dependent-address-summary">
            <span class="dependent-address-summary-label">Address</span>
            <div class="dependent-address-summary-value" id="uni_spouse_${idx}_address_summary">Same as primary member address</div>
          </div>
          <div class="dependent-address-fields">
          <div class="field"><label>Street Address</label><input type="text" id="uni_spouse_${idx}_street" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
          <div style="display:flex;margin-top:-5px;">
            <div style="flex:1; display:flex; flex-direction:column;">
              <label style="font-size:12px; margin-left:12px;">State</label>
              <select id="uni_spouse_${idx}_state" disabled style="padding:10px; font-size:14px; border-radius:8px; border:1px solid #d1d5db; background:#f3f4f6; cursor:not-allowed;">
                <option value="TX" selected>Texas</option>
              </select>
            </div>
            <div style="flex:1; display:flex; flex-direction:column;">
              <label style="font-size:12px; margin-left:12px;">City</label>
              <select id="uni_spouse_${idx}_city" style="padding:10px; margin-left:5px; font-size:14px; border-radius:8px; border:1px solid #d1d5db; color:#6b7280;">
                <option value="" disabled selected>City</option>
              </select>
            </div>
          </div>
          <div class="field"><label>ZIP Code</label><input type="text" id="uni_spouse_${idx}_zip" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
          <div class="field zone-field" id="uni_spouse_${idx}_center_field" style="display:none;">
            <label>Center / Zone</label>
            <div id="uni_spouse_${idx}_center_display"></div>
          </div>
          </div>
        </div>`;
            autoFillSpouseAddress(idx);
            loadCitiesFor('uni_spouse_' + idx, () => {
                autoFillSpouseAddress(idx);
                document.getElementById(`uni_spouse_${idx}_city`)?.setAttribute('disabled', 'disabled');
            });
            attachFieldFeedback(block);
            attachReadinessListeners();
            checkFormReadiness();
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
            const count = 1 + container.querySelectorAll('.member-card').length;
            const total = count * FLAT_FEE_PER_MEMBER;
            const fmt = '$' + total.toFixed(2);
            const feeEl = document.getElementById('flat_fee_display');
            const totalEl = document.getElementById('uni_order_total');
            const countEl = document.getElementById('flat_member_count');
            const btnLabel = document.getElementById('uni_submit_label');
            if (feeEl) feeEl.textContent = fmt;
            if (totalEl) totalEl.textContent = fmt;
            if (countEl) countEl.textContent = count + ' member' + (count !== 1 ? 's' : '') + ' × $' + FLAT_FEE_PER_MEMBER;
            if (btnLabel) btnLabel.innerHTML = btnLabel.innerHTML.replace(/Pay \$[\d,]+\.\d{2}/, 'Pay ' + fmt);
        }

        function addFlatMemberBlock() {
            const container = document.getElementById('flat_members_container');
            if (!container) return;
            const idx = flatMemberCount++;
            const displayNum = container.querySelectorAll('.member-card').length + 1;
            const block = document.createElement('div');
            block.className = 'member-card';
            block.id = 'flat_member_block_' + idx;
            block.innerHTML = `
        <div class="member-header dependent-card-header">
          <div class="dependent-card-meta">
            <div class="member-tag">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Member ${displayNum}
            </div>
          </div>
          <div class="dependent-scan-btn" onclick="openIdScan('flat_member_${idx}')">
            <svg viewBox="0 0 24 24" fill="none" stroke="#eaf7f3" stroke-width="2.6" style="width:26px;height:26px;"><path d="M8 4H6a2 2 0 0 0-2 2v2" stroke-linecap="round"/><path d="M16 4h2a2 2 0 0 1 2 2v2" stroke-linecap="round"/><path d="M8 20H6a2 2 0 0 1-2-2v-2" stroke-linecap="round"/><path d="M16 20h2a2 2 0 0 0 2-2v-2" stroke-linecap="round"/></svg>
            <span style="letter-spacing:0.3px;">Scan Your ID</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="#f7c873" stroke-width="2.4" style="width:22px;height:22px;"><path d="M12 3l2.2 5.2L20 10l-5.8 1.8L12 17l-2.2-5.2L4 10l5.8-1.8L12 3z" stroke-linejoin="round"/></svg>
          </div>
          <div class="dependent-card-actions">
            <button type="button" class="btn-remove-block" onclick="removeFlatBlock('flat_member_block_${idx}')" title="Remove Member">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </div>
        </div>
        <div class="fields-stack">
          <div class="field"><label>Email Address <span>*</span></label><input type="email" id="flat_member_${idx}_email" placeholder="member@example.com"><div id="flat_member_${idx}_email_msg" class="email-msg"></div></div>
          <div class="field"><label>Phone Number <span>*</span></label><input type="text" id="flat_member_${idx}_phone" placeholder="e.g. (832) 555-0100" oninput="restrictPhoneInput(this)" onblur="validateUsPhone(this)"><div id="flat_member_${idx}_phone_msg" class="phone-msg"></div></div>
          <div class="field"><label>First Name <span>*</span></label><input type="text" id="flat_member_${idx}_first_name" placeholder="Ahmad"></div>
          <div class="field"><label>Middle Name</label><input type="text" id="flat_member_${idx}_middle_name" placeholder="Middle Name (Optional)"></div>
          <div class="field"><label>Last Name <span>*</span></label><input type="text" id="flat_member_${idx}_last_name" placeholder="Ali"></div>
          <div class="field"><label>Date of Birth <span>*</span></label><input type="text" id="flat_member_${idx}_dob" placeholder="MM/DD/YYYY"><div class="dob-msg"></div></div>
          <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input type="text" id="flat_member_${idx}_txdl" placeholder="e.g. TX7234578"></div>
          <div class="field"><label>Relation <span>*</span></label><select id="flat_member_${idx}_relation"><option value="">Select Relation</option><option>Child</option><option>Sibling</option><option>Parent</option><option>Spouse</option></select></div>
          <div class="dependent-address-summary">
            <span class="dependent-address-summary-label">Address</span>
            <div class="dependent-address-summary-value" id="flat_member_${idx}_address_summary">Same as primary member address</div>
          </div>
          <div class="dependent-address-fields">
          <div class="field"><label>Street Address</label><input type="text" id="flat_member_${idx}_street" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
          <div style="display:flex;margin-top:-5px;">
            <div style="flex:1; display:flex; flex-direction:column;">
              <label style="font-size:12px; margin-left:12px;">State <span style="color:#ef4444;">*</span></label>
              <select id="flat_member_${idx}_state" disabled style="padding:10px; font-size:14px; border-radius:8px; border:1px solid #d1d5db; background:#f3f4f6; cursor:not-allowed;">
                <option value="TX" selected>Texas</option>
              </select>
            </div>
            <div style="flex:1; display:flex; flex-direction:column;">
              <label style="font-size:12px; margin-left:12px;">City <span style="color:#ef4444;">*</span></label>
              <select id="flat_member_${idx}_city" style="padding:10px; margin-left:5px; font-size:14px; border-radius:8px; border:1px solid #d1d5db;">
                <option value="" disabled selected>City</option>
              </select>
            </div>
          </div>
          <div class="field"><label>ZIP Code</label><input type="text" id="flat_member_${idx}_zip" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
          <div class="field zone-field" id="flat_member_${idx}_center_field" style="display:none;">
            <label>Center / Zone</label>
            <div id="flat_member_${idx}_center_display"></div>
          </div>
          </div>
        </div>`;
            container.appendChild(block);
            autoFillFlatMemberAddress(idx);
            loadCitiesFor('flat_member_' + idx, () => {
                autoFillFlatMemberAddress(idx);
                document.getElementById(`flat_member_${idx}_city`)?.setAttribute('disabled', 'disabled');
            });
            attachReadinessListeners();
            checkFormReadiness();
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
        function gv(id) {
            return (document.getElementById(id)?.value || '').trim();
        }

        function collectPrimary() {
            return {
                email: gv('uni_email'),
                phone: gv('uni_phone'),
                first_name: gv('uni_first_name'),
                middle_name: gv('uni_middle_name'),
                last_name: gv('uni_last_name'),
                dob: gv('uni_dob'),
                tx_dl: gv('uni_txdl'),
                street: gv('uni_street'),
                state: gv('uni_state'),
                city: gv('uni_city'),
                zip: gv('uni_zip'),
            };
        }

        function collectSpouses() {
            const container = document.getElementById('uni_spouses_container');
            if (!container) return [];
            const spouses = [];
            container.querySelectorAll('.spouse-block').forEach(block => {
                const idxMatch = block.id.match(/_(\d+)$/);
                const idx = idxMatch ? idxMatch[1] : '0';
                const ip = 'uni_spouse_' + idx + '_';
                const firstName = gv(ip + 'first_name');
                if (!firstName) return;
                const dobInput = block.querySelector('input[placeholder="MM/DD/YYYY"]');
                spouses.push({
                    first_name: firstName,
                    last_name: gv(ip + 'last_name'),
                    middle_name: gv(ip + 'middle_name'),
                    dob: (dobInput?.value || '').trim(),
                    phone: gv(ip + 'phone'),
                    tx_dl: gv(ip + 'txdl'),
                    email: gv(ip + 'email'),
                    gender: gv(ip + 'gender'),
                    street: gv(ip + 'street'),
                    city: gv(ip + 'city'),
                    zip: gv(ip + 'zip'),
                    state: gv(ip + 'state'),
                });
            });
            return spouses;
        }

        // ─── SPOUSE ADDRESS AUTO-FILL ─────────────────────────────────────────────
        function autoFillSpouseAddress(idx) {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val;
            };
            const ip = 'uni_spouse_' + idx + '_';
            const street = document.getElementById('uni_street')?.value || '';
            const city = document.getElementById('uni_city')?.value || '';
            const zip = document.getElementById('uni_zip')?.value || '';
            const state = document.getElementById('uni_state')?.value || '';
            const zone = _zipState['uni']?.zone || '';
            set(ip + 'street', street);
            set(ip + 'city', city);
            set(ip + 'zip', zip);
            set(ip + 'state', state);
            setDependentAddressSummary(`uni_spouse_${idx}_address_summary`, street, city, state, zip, zone);
        }

        function autoFillSpouseAddresses() {
            const container = document.getElementById('uni_spouses_container');
            if (!container) return;
            container.querySelectorAll('.spouse-block').forEach(block => {
                const idxMatch = block.id.match(/_(\d+)$/);
                if (idxMatch) autoFillSpouseAddress(idxMatch[1]);
            });
        }

        // ─── PRIMARY ADDRESS CHANGE HANDLER ──────────────────────────────────────
        function onPrimaryAddressChange() {
            const cfg = MEMBERSHIP_CONFIG[currentMembershipType];
            if (!cfg) return;
            const spouseFormVisible = document.getElementById('uni_spouse_form_wrap')?.style.display !== 'none';
            if (cfg.hasSpouse && spouseFormVisible) autoFillSpouseAddresses();
            if (cfg.hasFlatMembers) autoFillFlatMemberAddresses();
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
                    first_name: firstName,
                    middle_name: gv('flat_member_' + midx + '_middle_name'),
                    last_name: gv('flat_member_' + midx + '_last_name'),
                    phone: gv('flat_member_' + midx + '_phone'),
                    dob: gv('flat_member_' + midx + '_dob'),
                    tx_dl: gv('flat_member_' + midx + '_txdl'),
                    email: gv('flat_member_' + midx + '_email'),
                    relation: gv('flat_member_' + midx + '_relation') || 'Family Member',
                    street: gv('flat_member_' + midx + '_street'),
                    city: gv('flat_member_' + midx + '_city'),
                    zip: gv('flat_member_' + midx + '_zip'),
                    state: gv('flat_member_' + midx + '_state'),
                });
            });
            return members;
        }

        // ─── FLAT MEMBER ADDRESS AUTO-FILL ───────────────────────────────────────
        function autoFillFlatMemberAddress(idx) {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val;
            };
            const street = document.getElementById('uni_street')?.value || '';
            const city = document.getElementById('uni_city')?.value || '';
            const zip = document.getElementById('uni_zip')?.value || '';
            const state = document.getElementById('uni_state')?.value || '';
            const zone = _zipState['uni']?.zone || '';
            set('flat_member_' + idx + '_street', street);
            set('flat_member_' + idx + '_city', city);
            set('flat_member_' + idx + '_zip', zip);
            set('flat_member_' + idx + '_state', state);
            setDependentAddressSummary(`flat_member_${idx}_address_summary`, street, city, state, zip, zone);
        }

        function autoFillFlatMemberAddresses() {
            const container = document.getElementById('flat_members_container');
            if (!container) return;
            container.querySelectorAll('.member-card').forEach(block => {
                const m = block.id.match(/_(\d+)$/);
                if (m) autoFillFlatMemberAddress(m[1]);
            });
        }

        function collectTerms() {
            const cb = document.getElementById('uni_terms');
            const agreed = cb?.checked ?? false;
            return {
                agree: agreed,
                responsibility: agreed,
                privacy: agreed,
                communications: agreed,
                auto_renewal: document.getElementById('uni_auto_renewal')?.checked ?? false,
            };
        }


        // ─── SUBMISSION ──────────────────────────────────────────────────────────
        // ─── FORM READINESS ──────────────────────────────────────────────────────
        function checkFormReadiness() {
            const btn = document.getElementById('uni_submit_btn');
            if (!btn) return;

            const cfg = MEMBERSHIP_CONFIG[currentMembershipType];
            if (!cfg) { btn.disabled = true; return; }

            // Required primary text fields
            const requiredIds = ['uni_first_name', 'uni_last_name', 'uni_street', 'uni_state', 'uni_city', 'uni_zip'];
            for (const id of requiredIds) {
                const el = document.getElementById(id);
                if (!el || !el.value.trim()) { btn.disabled = true; return; }
            }

            // Phone — must be 10 digits
            const phone = document.getElementById('uni_phone');
            if (!phone || phone.value.replace(/\D/g,'').length !== 10) { btn.disabled = true; return; }

            // DOB — must match MM/DD/YYYY
            const dob = document.getElementById('uni_dob');
            if (!dob || !/^\d{2}\/\d{2}\/\d{4}$/.test(dob.value.trim())) { btn.disabled = true; return; }

            // ZIP must have been validated successfully
            if (!_zipState['uni']?.valid) { btn.disabled = true; return; }

            // Terms checkbox
            const terms = document.getElementById('uni_terms');
            if (!terms?.checked) { btn.disabled = true; return; }

            // Card details
            const cardholderName = document.getElementById('uni_cardholder_name');
            if (!cardholderName || !cardholderName.value.trim()) { btn.disabled = true; return; }

            // Spouse fields — only when the form is actually visible
            const spouseFormWrap = document.getElementById('uni_spouse_form_wrap');
            const spouseFormVisible = spouseFormWrap && spouseFormWrap.style.display !== 'none';
            if (cfg.hasSpouse && spouseFormVisible) {
                const container = document.getElementById('uni_spouses_container');
                if (container) {
                    const spouseBlocks = container.querySelectorAll('[id^="uni_spouse_block_"]');
                    for (const block of spouseBlocks) {
                        const fields = ['first_name', 'last_name', 'dob', 'phone'];
                        for (const f of fields) {
                            const el = block.querySelector(`[id$="_${f}"]`);
                            if (!el || !el.value.trim()) { btn.disabled = true; return; }
                        }
                    }
                }
            }

            // Flat member fields — only when the section is visible
            const flatSection = document.getElementById('uni_flat_members_section');
            if (cfg.hasFlatMembers && flatSection && flatSection.style.display !== 'none') {
                const container = document.getElementById('flat_members_container');
                if (container) {
                    const memberBlocks = container.querySelectorAll('[id^="flat_member_block_"]');
                    for (const block of memberBlocks) {
                        const fields = ['first_name', 'last_name', 'dob', 'phone'];
                        for (const f of fields) {
                            const el = block.querySelector(`[id$="_${f}"]`);
                            if (!el || !el.value.trim()) { btn.disabled = true; return; }
                        }
                    }
                }
            }

            // Block if any field currently has an active validation error (email/phone checks)
            const _valSection = document.getElementById('unifiedMembershipSection');
            if (_valSection) {
                const _valInputs = _valSection.querySelectorAll('input:not([readonly])');
                for (const _inp of _valInputs) {
                    if (_inp._hasValidationError === true) { btn.disabled = true; return; }
                }
            }

            btn.disabled = false;
        }

        function attachReadinessListeners() {
            const section = document.getElementById('unifiedMembershipSection');
            if (!section) return;
            section.querySelectorAll('input:not([readonly]), select').forEach(el => {
                if (el._readinessAttached) return;
                el._readinessAttached = true;
                el.addEventListener('input', checkFormReadiness);
                el.addEventListener('change', checkFormReadiness);
            });
        }

        // ─── CONFIRM POPUP ───────────────────────────────────────────────────────
        function submitUnifiedMembership() {
            // Re-run readiness check before showing popup
            checkFormReadiness();
            const btn = document.getElementById('uni_submit_btn');
            if (btn?.disabled) return;
            document.getElementById('confirmOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeConfirmPopup() {
            document.getElementById('confirmOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }

        function proceedSubmission() {
            closeConfirmPopup();
            submitMembership(currentMembershipType);
        }

        // Close on overlay backdrop click
        document.getElementById('confirmOverlay')?.addEventListener('click', function(e) {
            if (e.target === this) closeConfirmPopup();
        });

        document.getElementById('primaryReviewOverlay')?.addEventListener('click', function(e) {
            if (e.target === this) closePrimaryReview();
        });

        // ─── TIMER ───────────────────────────────────────────────────────────────
        let _timerStart = 0,
            _timerInterval = null;

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
            const subEl = document.getElementById('overlaySub');
            if (stepEl) stepEl.textContent = text;
            if (subEl) subEl.textContent = sub || 'Please do not close this window.';
            [1, 2].forEach(n => {
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
            const prev = input.value;

            let v = prev.replace(/\D/g, '');
            if (v.length > 8) v = v.slice(0, 8);

            let out = v;

            if (v.length > 4)
                out = v.slice(0, 2) + '/' + v.slice(2, 4) + '/' + v.slice(4);
            else if (v.length > 2)
                out = v.slice(0, 2) + '/' + v.slice(2);

            input.value = out;

            const added = out.length - prev.length;
            try {
                input.setSelectionRange(cursor + added, cursor + added);
            } catch (_) {}
        }

        function validateDob(input) {
            const val = input.value.trim();
            const msgEl = input.nextElementSibling;

            const error = (msg) => {
                input.style.borderColor = '#dc2626';
                if (msgEl) msgEl.textContent = msg;
                return false;
            };

            const ok = () => {
                input.style.borderColor = '#10b981';
                if (msgEl) msgEl.textContent = '';
                return true;
            };

            if (!val) {
                input.style.borderColor = '';
                if (msgEl) msgEl.textContent = '';
                return true;
            }

            const m = val.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            if (!m) return error("Please enter a valid date.");

            const month = +m[1];
            const day = +m[2];
            const year = +m[3];

            // 🔴 HARD INVALID DATE CHECK
            if (!isRealDate(month, day, year)) {
                return error("Please enter a valid date.");
            }

            const date = new Date(year, month - 1, day);

            if (date > new Date()) {
                return error("Please enter a valid date.");
            }

            // age check
            let age = new Date().getFullYear() - year;

            const today = new Date();
            const birthdayPassed =
                today.getMonth() > (month - 1) ||
                (today.getMonth() === (month - 1) && today.getDate() >= day);

            if (!birthdayPassed) age--;

            if (age < 18) {
                return error("Member must be 18 or older to register.");
            }

            return ok();
        }

        function isRealDate(month, day, year) {
            const d = new Date(year, month - 1, day);

            return (
                d.getFullYear() === year &&
                d.getMonth() === month - 1 &&
                d.getDate() === day
            );
        }
        // ─── REQUIRED-FIELD HIGHLIGHT (replaces alert) ────────────────────────────
        function highlightMissingFields(sectionEl) {
            let firstBad = null;
            sectionEl.querySelectorAll('.field').forEach(field => {
                if (field.closest('.zone-field')) return;
                const label = field.querySelector('label');
                const required = label && label.querySelector('span');
                if (!required) return;
                const inp = field.querySelector('input:not([readonly]):not([type="checkbox"]), select');
                if (!inp) return;
                const empty = !inp.value.trim();
                if (empty) {
                    inp.classList.add('field-invalid');
                    inp.classList.remove('field-valid');
                    if (!firstBad) firstBad = inp;
                } else {
                    inp.classList.remove('field-invalid');
                    inp.classList.add('field-valid');
                }
            });
            if (firstBad) {
                firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstBad.focus();
                return false;
            }
            return true;
        }

        // ─── REAL-TIME GREEN/RED FEEDBACK ON REQUIRED FIELDS ─────────────────────
        function attachFieldFeedback(sectionEl) {
            sectionEl.querySelectorAll('.field').forEach(field => {
                if (field.closest('.zone-field')) return;
                const label = field.querySelector('label');
                const required = label && label.querySelector('span');
                if (!required) return;
                const inp = field.querySelector('input:not([readonly]):not([type="checkbox"]), select');
                if (!inp || inp._feedbackAttached) return;
                inp._feedbackAttached = true;
                const update = () => {
                    if (!inp.value.trim()) {
                        inp.classList.add('field-invalid');
                        inp.classList.remove('field-valid');
                    } else {
                        inp.classList.remove('field-invalid');
                        inp.classList.add('field-valid');
                    }
                };
                inp.addEventListener('input', update);
                inp.addEventListener('change', update);
                inp.addEventListener('blur', update);
            });
        }

        function validateUsPhone(input) {
            const msgEl = document.getElementById(input.id + '_msg');

            let raw = input.value || "";

            // ✅ Extract digits only (hard limit 10)
            let digits = raw.replace(/\D/g, "").slice(0, 10);

            // ✅ Format
            let formatted = "";
            if (digits.length > 0) {
                formatted = "(" + digits.substring(0, 3);
            }
            if (digits.length >= 4) {
                formatted += ") " + digits.substring(3, 6);
            }
            if (digits.length >= 7) {
                formatted += "-" + digits.substring(6, 10);
            }

            // ✅ Update value ONLY if different (prevents cursor jump issues)
            if (input.value !== formatted) {
                input.value = formatted;
            }

            const isComplete = digits.length === 10;

            // ✅ Empty state
            if (digits.length === 0) {
                if (msgEl) {
                    msgEl.textContent = "Phone number is required";
                    msgEl.className = "phone-msg error";
                }
                input.style.borderColor = "#dc2626";
                input._hasValidationError = true;
                checkFormReadiness();
                updateConfirmBtn();
                return false;
            }

            // ✅ While typing (less aggressive UX)
            if (!isComplete) {
                if (msgEl) {
                    msgEl.textContent = "Enter 10 digits";
                    msgEl.className = "phone-msg error";
                }
                input.style.borderColor = "#dc2626";
                input._hasValidationError = true;
                checkFormReadiness();
                updateConfirmBtn();
                return false;
            }

            // ── Cross-form uniqueness: check against all other phone fields ──
            const _phoneSec = document.getElementById('unifiedMembershipSection');
            if (_phoneSec) {
                const _pSiblings = [..._phoneSec.querySelectorAll('input[id$="_phone"]')];
                const _pDup = _pSiblings.some(o => o !== input && o.value.trim().replace(/\D/g, '') === digits);
                if (_pDup) {
                    if (msgEl) { msgEl.textContent = 'This phone is already used by another member in this form.'; msgEl.className = 'phone-msg error'; }
                    input.style.borderColor = '#dc2626';
                    input._hasValidationError = true;
                    checkFormReadiness();
                    updateConfirmBtn();
                    return false;
                }
                // This field is now unique — re-validate siblings that showed a duplicate warning
                _pSiblings.forEach(o => {
                    if (o !== input && o.value.trim() && o.style.borderColor === 'rgb(220, 38, 38)') {
                        validateUsPhone(o);
                    }
                });
            }

            // ✅ Valid
            if (msgEl) {
                msgEl.textContent = "✓ Valid phone number";
                msgEl.className = "phone-msg success";
            }

            input.style.borderColor = "#10b981";
            input._hasValidationError = false;
            updateConfirmBtn();
            // Kick off async WA phone-existence check (non-blocking)
            checkMemberPhone(input);
            return true;
        }

        // ─── WA PHONE CHECK (real-time, fires after format + uniqueness pass) ──────
        async function checkMemberPhone(input) {
            const phone = input.value.trim();
            const msgEl = document.getElementById(input.id + '_msg');

            input._waPhoneExists = false;

            if (!phone || phone.replace(/\D/g, '').length < 10) return;

            if (msgEl) { msgEl.textContent = 'Checking…'; msgEl.className = 'phone-msg'; }
            input._hasValidationError = true;
            checkFormReadiness();
            updateConfirmBtn();

            try {
                const res = await fetch('{{ route('membership.check-phone') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ phone }),
                });
                const data = await res.json();
                if (data.exists) {
                    input._waPhoneExists = true;
                    input._hasValidationError = true;
                    input.style.borderColor = '#dc2626';
                    if (msgEl) { msgEl.textContent = 'This phone is already registered as an ISGH member.'; msgEl.className = 'phone-msg error'; }
                } else {
                    input._waPhoneExists = false;
                    input._hasValidationError = false;
                    input.style.borderColor = '#10b981';
                    if (msgEl) { msgEl.textContent = '✓ Phone is available'; msgEl.className = 'phone-msg success'; }
                }
                checkFormReadiness();
                updateConfirmBtn();
            } catch (e) {
                // On network error don't block — leave the green "valid" state
                input._waPhoneExists = false;
                input._hasValidationError = false;
                checkFormReadiness();
                updateConfirmBtn();
            }
        }

        // ─── WA EMAIL CHECK (spouse / flat members only — real-time with debounce) ──
        const _emailCheckTimers = {};

        async function checkMemberEmail(input) {
            const email = input.value.trim();
            const msgEl = input.id ? document.getElementById(input.id + '_msg') : null;

            // Reset state immediately when field changes
            input._waEmailExists = false;
            input._hasValidationError = false;
            input.style.borderColor = '';
            if (msgEl) {
                msgEl.textContent = '';
                msgEl.className = 'email-msg';
            }

            if (!email) { checkFormReadiness(); return; }

            // Show "checking…" while waiting for a valid format
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                if (msgEl) {
                    msgEl.textContent = 'Enter a valid email address.';
                    msgEl.className = 'email-msg error';
                }
                input._hasValidationError = true;
                checkFormReadiness();
                return;
            }

            // ── Cross-form uniqueness: check against ALL email fields including readonly primary ──
            const _sectionEl = document.getElementById('unifiedMembershipSection');
            if (_sectionEl) {
                const _allEmails = [..._sectionEl.querySelectorAll('input[type="email"]')];
                const _isDup = _allEmails.some(o => o !== input && o.value.trim().toLowerCase() === email.toLowerCase());
                if (_isDup) {
                    input._waEmailExists = undefined; // block submit
                    input._hasValidationError = true;
                    input.style.borderColor = '#dc2626';
                    if (msgEl) { msgEl.textContent = 'This email is already used by another member in this form.'; msgEl.className = 'email-msg error'; }
                    checkFormReadiness();
                    return;
                }
                // Re-check only editable siblings that still show a cross-form duplicate warning
                _allEmails.filter(o => !o.readOnly && o !== input && o.value.trim() && o._waEmailExists === undefined)
                          .forEach(o => checkMemberEmail(o));
            }

            if (msgEl) {
                msgEl.textContent = 'Checking…';
                msgEl.className = 'email-msg';
            }
            input._hasValidationError = true;
            checkFormReadiness();

            try {
                const res = await fetch('{{ route('membership.check-email') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email
                    }),
                });
                const data = await res.json();
                if (data.exists) {
                    input._waEmailExists = true;
                    input._hasValidationError = true;
                    input.style.borderColor = '#dc2626';
                    if (msgEl) {
                        msgEl.textContent = 'This email is already taken — already registered as an ISGH member.';
                        msgEl.className = 'email-msg error';
                    }
                } else {
                    input._waEmailExists = false;
                    input._hasValidationError = false;
                    input.style.borderColor = '#10b981';
                    if (msgEl) {
                        msgEl.textContent = '✓ Email is available';
                        msgEl.className = 'email-msg success';
                    }
                }
                checkFormReadiness();
            } catch (e) {
                input._waEmailExists = false;
                input._hasValidationError = false;
                if (msgEl) {
                    msgEl.textContent = '';
                    msgEl.className = 'email-msg';
                }
                checkFormReadiness();
            }
        }

        function _scheduleEmailCheck(input) {
            if (!input.id || !input.id.endsWith('_email')) return;
            clearTimeout(_emailCheckTimers[input.id]);
            _emailCheckTimers[input.id] = setTimeout(() => checkMemberEmail(input), 600);
        }

        // Real-time: fires 600 ms after the user stops typing
        document.addEventListener('input', function(e) {
            if (e.target.type === 'email' && !e.target.readOnly) _scheduleEmailCheck(e.target);
        });

        // Immediate check on blur (in case user pastes and tabs away)
        document.addEventListener('blur', function(e) {
            if (e.target.type === 'email' && !e.target.readOnly && e.target.id && e.target.id.endsWith('_email')) {
                clearTimeout(_emailCheckTimers[e.target.id]);
                checkMemberEmail(e.target);
            }
        }, true);

        // ─── TERMS MODAL ─────────────────────────────────────────────────────────
        let _termsSourceCheckbox = null;

        function openTermsModal(event) {
            event.preventDefault();
            // Find the nearest checkbox in the same checkbox-box wrapper
            const box = event.target.closest('.checkbox-box');
            _termsSourceCheckbox = box?.querySelector('input[type="checkbox"]') ?? null;
            const modal = document.getElementById('termsModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeTermsModal() {
            const modal = document.getElementById('termsModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
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

        document.addEventListener('change', function(e) {
            if (e.target.placeholder === 'MM/DD/YYYY') {
                validateDob(e.target);
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('termsModal')?.addEventListener('click', function(e) {
                if (e.target === this) closeTermsModal();
            });
            updateFlatTotal(); // initialise flat fee display on load
        });

        document.addEventListener("DOMContentLoaded", () => {
            initCityDropdowns();
        });

        // ─── STRIPE ELEMENTS INIT ─────────────────────────────────────────────────
        const _stripePublishableKey = '{{ config("services.stripe.key") }}';
        let _stripe = null;
        let _cardElement = null;

        function initStripeCardElement() {
            if (_cardElement) return; // already mounted
            if (!_stripePublishableKey) {
                console.warn('[Stripe] publishable key not set');
                return;
            }
            _stripe = Stripe(_stripePublishableKey);
            const elements = _stripe.elements();
            _cardElement = elements.create('card', {
                style: {
                    base: {
                        fontFamily: "'SF Pro regular', sans-serif",
                        fontSize: '14px',
                        color: '#111827',
                        '::placeholder': { color: '#c0c8d4' },
                    },
                    invalid: { color: '#dc2626' },
                },
                hidePostalCode: true,
            });
            _cardElement.mount('#stripe-card-element');
            _cardElement.on('change', e => {
                const errEl = document.getElementById('stripe-card-error');
                if (errEl) errEl.textContent = e.error ? e.error.message : '';
            });
        }

        document.addEventListener('input', function(e) {
            if (e.target?.id === 'uni_cardholder_name') {
                if (e.target.value.trim()) e.target.classList.remove('field-invalid');
            }
        });

        // ─── ZIP VALIDATION ──────────────────────────────────────────────────────
        const _zipState = {}; // prefix → { valid: bool, zone: string }

        function _populateDonationTypes(types) {
            const selectEl = document.getElementById('uni_donation_type');
            if (!selectEl) return;
            selectEl.innerHTML = '<option value="">— Select Donation Type —</option>';
            types.forEach(type => {
                const opt = document.createElement('option');
                opt.value = type;
                opt.textContent = type;
                selectEl.appendChild(opt);
            });
        }

        function _onCenterChange(prefix, selectEl) {
            const centerName = selectEl.value;
            _zipState[prefix].zone = centerName;
            if (prefix !== 'uni' || !centerName) return;
            const _selType = document.getElementById('membershipSelector')?.value ?? '';
            const _isChk = _selType === 'checkomatic_family' || _selType === 'checkomatic_individual';
            if (!_isChk) return;
            const idx = (_zipState[prefix].centers ?? []).indexOf(centerName);
            const types = (_zipState[prefix].donationTypesByCenter ?? [])[idx] ?? [];
            _zipState[prefix].donationTypes = types;
            _populateDonationTypes(types);
        }

        async function validateZip(input, prefix) {
            const zip = input.value.trim();
            const msgEl = document.getElementById(prefix + '_zip_msg');
            const cfEl = document.getElementById(prefix + '_center_field');
            const cdEl = document.getElementById(prefix + '_center_display');

            _zipState[prefix] = { valid: false, zone: '', centers: [], donationTypesByCenter: [], donationTypes: [] };
            if (msgEl) {
                msgEl.textContent = '';
                msgEl.className = 'zip-msg';
            }
            if (cfEl) cfEl.style.display = 'none';

            if (!zip || zip.replace(/\D/g, '').length < 5) return;

            try {
                const res = await fetch('/membership/zip-lookup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ zip }),
                });
                const data = await res.json();
                const centers = Array.isArray(data.centers.centers) ? data.centers.centers : [];
                const rawDonationTypes = Array.isArray(data.centers.donation_types) ? data.centers.donation_types : [];

                // Each rawDonationTypes[i] is a comma-separated string for centers[i]
                const donationTypesByCenter = rawDonationTypes.map(s =>
                    s.split(',').map(t => t.trim()).filter(Boolean)
                );

                _zipState[prefix] = {
                    valid: true,
                    zone: '',
                    centers,
                    donationTypesByCenter,
                    donationTypes: [],
                };

                if (!data.success || centers.length === 0) {
                    if (cdEl) cdEl.innerHTML = '';
                    if (cfEl) cfEl.style.display = 'none';
                    if (msgEl) {
                        msgEl.textContent = 'No ISGH center is assigned for this ZIP. You can continue without selecting a center.';
                        msgEl.className = 'zip-msg success';
                    }
                    checkFormReadiness();
                    if (prefix === 'uni') updateConfirmBtn();
                    return;
                }

                const _selType = document.getElementById('membershipSelector')?.value ?? '';
                const _isChk = _selType === 'checkomatic_family' || _selType === 'checkomatic_individual';

                if (centers.length === 1) {
                    _zipState[prefix].zone = centers[0];
                    _zipState[prefix].donationTypes = donationTypesByCenter[0] ?? [];
                    if (cdEl) cdEl.innerHTML = `<span class="zone-text">📍 ${centers[0]}</span>`;
                    if (cfEl) cfEl.style.display = '';
                    if (msgEl) {
                        msgEl.textContent = '✓ Center assigned';
                        msgEl.className = 'zip-msg success';
                    }
                    if (prefix === 'uni' && _isChk) _populateDonationTypes(_zipState[prefix].donationTypes);
                } else {
                    // Multiple centers — donation types update when the user picks a center
                    if (prefix === 'uni' && _isChk) _populateDonationTypes([]); // reset until center is chosen
                    const opts = centers.map(c => `<option value="${c}">${c}</option>`).join('');
                    if (cdEl) cdEl.innerHTML =
                        `<select onchange="_onCenterChange('${prefix}', this)"><option value="">Select your center…</option>${opts}</select>`;
                    if (cfEl) cfEl.style.display = '';
                    if (msgEl) {
                        msgEl.textContent = '✓ Multiple centers found — please select one';
                        msgEl.className = 'zip-msg success';
                    }
                }
            } catch (e) {
                if (msgEl) {
                    msgEl.textContent = 'ZIP lookup failed. Please try again.';
                    msgEl.className = 'zip-msg error';
                    console.error('ZIP lookup error:', e);
                }
            }
            checkFormReadiness();
            if (prefix === 'uni') updateConfirmBtn();
        }

        // ─── SUBMISSION ──────────────────────────────────────────────────────────
        function _submitAbort(msg, focusEl) {
            console.warn('[submitMembership] aborted:', msg);
            if (focusEl) {
                focusEl.focus();
                focusEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            showSubmitError(msg);
        }

        function showSubmitError(msg) {
            const el = document.getElementById('uni_submit_error');
            if (!el) { alert(msg); return; }
            el.textContent = msg;
            el.style.display = 'block';
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => { el.style.display = 'none'; }, 8000);
        }

        async function submitMembership(type) {
            const el_error = document.getElementById('uni_submit_error');
            if (el_error) el_error.style.display = 'none';

            console.log('[submitMembership] called with type:', type);

            if (!type) { _submitAbort('Please select a membership type.'); return; }

            const cfg = MEMBERSHIP_CONFIG[type];
            const primary = collectPrimary();
            const spouseFormVisible = document.getElementById('uni_spouse_form_wrap')?.style.display !== 'none';
            const spouses = (cfg?.hasSpouse && spouseFormVisible) ? collectSpouses() : [];
            const flatMembers = cfg?.hasFlatMembers ? collectFlatMembers() : [];
            const terms = collectTerms();

            console.log('[submitMembership] collected:', { primary, spouses, flatMembers, terms });

            const sectionEl = document.getElementById('unifiedMembershipSection');

            // Required fields highlight check
            // if (sectionEl && !highlightMissingFields(sectionEl)) {
            //     _submitAbort('Please fill in all required fields highlighted in red.');
            //     return;
            // }

            if (!primary.first_name || !primary.last_name || !primary.email) {
                _submitAbort('Please fill in your First Name, Last Name, and Email Address.');
                return;
            }

            const cardholderInput = document.getElementById('uni_cardholder_name');
            const cardholderName = cardholderInput?.value.trim() || '';
            if (!cardholderName) {
                cardholderInput?.classList.add('field-invalid');
                _submitAbort('Please enter the name on card.', cardholderInput);
                return;
            }

            // Spouse required-field validation (only when form is visible)
            if (spouseFormVisible) {
                const spouseWrap = document.getElementById('uni_spouse_form_wrap');
                if (spouseWrap && !highlightMissingFields(spouseWrap)) {
                    _submitAbort('Please fill in all required spouse fields highlighted in red.');
                    return;
                }
            }

            // Flat family member required-field validation
            if (cfg?.hasFlatMembers) {
                const flatSection = document.getElementById('uni_flat_members_section');
                if (flatSection && !highlightMissingFields(flatSection)) {
                    _submitAbort('Please fill in all required family member fields highlighted in red.');
                    return;
                }
            }

            // Phone validation — primary + all spouse/member phone inputs
            const allPhoneInputs = sectionEl
                ? [...sectionEl.querySelectorAll('input[id$="_phone"]')]
                : [document.getElementById('uni_phone')].filter(Boolean);
            for (const phoneInput of allPhoneInputs) {
                if (phoneInput.value.trim() && !validateUsPhone(phoneInput)) {
                    _submitAbort('Please enter a valid 10-digit US phone number.', phoneInput);
                    return;
                }
            }
            const primaryPhone = document.getElementById('uni_phone');
            if (primaryPhone && !primaryPhone.value.trim()) {
                primaryPhone.classList.add('field-invalid');
                _submitAbort('Phone number is required.', primaryPhone);
                return;
            }

            // DOB validation
            if (sectionEl) {
                for (const dobInput of [...sectionEl.querySelectorAll('input[placeholder="MM/DD/YYYY"]')]) {
                    if (!validateDob(dobInput)) {
                        _submitAbort('Please enter a valid date of birth (MM/DD/YYYY).', dobInput);
                        return;
                    }
                }
            }

            // ZIP / center validation
            if (!_zipState['uni']?.valid) {
                const zipInput = document.getElementById('uni_zip');
                if (zipInput) zipInput.classList.add('field-invalid');
                _submitAbort('Please enter a valid ZIP code.', zipInput);
                return;
            }
            const zone = _zipState['uni']?.zone || '';

            // Cross-member uniqueness: each email must be unique within this form
            if (sectionEl) {
                const _allEmails = [...sectionEl.querySelectorAll('input[type="email"]:not([readonly])')];
                const _emailVals = _allEmails.map(i => i.value.trim().toLowerCase());
                for (let _i = 0; _i < _emailVals.length; _i++) {
                    if (!_emailVals[_i]) continue;
                    if (_emailVals.indexOf(_emailVals[_i]) !== _i) {
                        _allEmails[_i].classList.add('field-invalid');
                        const _msgEl = document.getElementById(_allEmails[_i].id + '_msg');
                        if (_msgEl) { _msgEl.textContent = 'This email is already used by another member in this form.'; _msgEl.className = 'email-msg error'; }
                        _submitAbort('Each member must have a unique email address.', _allEmails[_i]);
                        return;
                    }
                }
            }

            // Cross-member uniqueness: each phone must be unique within this form
            if (sectionEl) {
                const _allPhones = [...sectionEl.querySelectorAll('input[id$="_phone"]')];
                const _phoneVals = _allPhones.map(i => i.value.trim().replace(/\D/g, ''));
                for (let _i = 0; _i < _phoneVals.length; _i++) {
                    if (!_phoneVals[_i]) continue;
                    if (_phoneVals.indexOf(_phoneVals[_i]) !== _i) {
                        _allPhones[_i].classList.add('field-invalid');
                        const _msgEl = document.getElementById(_allPhones[_i].id + '_msg');
                        if (_msgEl) { _msgEl.textContent = 'This phone is already used by another member in this form.'; _msgEl.className = 'phone-msg error'; }
                        _submitAbort('Each member must have a unique phone number.', _allPhones[_i]);
                        return;
                    }
                }
            }

            // Member email duplicate check (against existing WA records)
            if (sectionEl) {
                for (const emailInput of [...sectionEl.querySelectorAll('input[type="email"]:not([readonly])')]) {
                    if (emailInput._waEmailExists) {
                        emailInput.classList.add('field-invalid');
                        const msgEl = emailInput.id ? document.getElementById(emailInput.id + '_msg') : null;
                        if (msgEl) { msgEl.textContent = 'This email is already registered.'; msgEl.className = 'email-msg error'; }
                        _submitAbort('A member email is already registered. Please use a different email.', emailInput);
                        return;
                    }
                    if (emailInput.value.trim() && emailInput._waEmailExists === undefined) {
                        _submitAbort('Email verification is still pending. Please wait a moment and try again.', emailInput);
                        return;
                    }
                }
            }

            // Checkomatic amount validation
            if (type === 'checkomatic_family' || type === 'checkomatic_individual') {
                const amtInput = document.getElementById('uni_checkomatic_amount');
                const amt = parseFloat(amtInput?.value);
                const amtMin = parseFloat(amtInput?.min) || 10;
                if (isNaN(amt) || amt < amtMin) {
                    updateCheckomaticNote();
                    amtInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    _submitAbort(`Minimum monthly amount is $${amtMin.toFixed(2)}.`);
                    return;
                }
            }

            // Terms
            if (!terms.agree) {
                _submitAbort('You must agree to the Terms and Conditions to continue.');
                return;
            }

            console.log('[submitMembership] all validations passed — creating Stripe payment method');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const overlay = document.getElementById('submitOverlay');
            const btn = document.getElementById('uni_submit_btn');
            const btnLabel = document.getElementById('uni_submit_label');

            if (overlay) overlay.classList.add('visible');
            if (btn) btn.disabled = true;
            if (btnLabel) btnLabel.textContent = 'Processing…';
            startTimer();
            setOverlayStep(1, 'Verifying card details…', 'Securely tokenising your payment information.');

            // ── Stripe createPaymentMethod ─────────────────────────────────────────
            if (!_stripe || !_cardElement) {
                stopTimer();
                if (overlay) overlay.classList.remove('visible');
                if (btn) btn.disabled = false;
                if (btnLabel) btnLabel.innerHTML = cfg?.submitLabel || 'Complete Registration';
                _submitAbort('Card element is not ready. Please refresh the page and try again.');
                return;
            }

            const { paymentMethod, error: pmError } = await _stripe.createPaymentMethod({
                type: 'card',
                card: _cardElement,
                billing_details: {
                    name: cardholderName,
                    email: primary.email,
                    phone: primary.phone,
                    address: {
                        line1: primary.street,
                        city: primary.city,
                        state: primary.state,
                        postal_code: primary.zip,
                        country: 'US',
                    },
                },
            });

            if (pmError) {
                console.warn('[submitMembership] createPaymentMethod error:', pmError);
                stopTimer();
                if (overlay) overlay.classList.remove('visible');
                if (btn) btn.disabled = false;
                if (btnLabel) btnLabel.innerHTML = cfg?.submitLabel || 'Complete Registration';
                const cardErrEl = document.getElementById('stripe-card-error');
                if (cardErrEl) cardErrEl.textContent = pmError.message;
                _submitAbort(pmError.message);
                return;
            }

            const paymentMethodId = paymentMethod.id;
            console.log('[submitMembership] payment method created:', paymentMethodId);
            setOverlayStep(1, 'Saving your registration…', 'Securely sending your information.');

            try {
                const res = await fetch('/membership/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ membership_type: type, primary, spouses, flat_members: flatMembers, terms, zone, donation_type: document.getElementById('uni_donation_type')?.value || null, payment_method_id: paymentMethodId, checkomatic_amount: (type === 'checkomatic_family' || type === 'checkomatic_individual') ? (parseInt(document.getElementById('uni_checkomatic_amount')?.value, 10) || getCheckomaticMinimum(type)) : null }),
                });

                console.log('[submitMembership] response status:', res.status);

                let data;
                const rawText = await res.text();
                console.log('[submitMembership] raw response:', rawText.slice(0, 500));
                try { data = JSON.parse(rawText); }
                catch (parseErr) {
                    throw new Error('Server returned an unexpected response (HTTP ' + res.status + '). Check Laravel logs.');
                }

                console.log('[submitMembership] parsed data:', data);

                if (!data.success) {
                    const errLines = data.errors ? Object.values(data.errors).flat() : [];
                    const msg = data.message || (errLines.length ? errLines.join('\n') : 'Submission failed — please check your form.');
                    throw new Error(msg);
                }

                // if (!data.checkout_url) {
                //     throw new Error('Payment URL was not returned. Please try again or contact support.');
                // }

                // setOverlayStep(2, 'Redirecting to Stripe…', 'You will be taken to the secure Stripe payment page.');
                // stopTimer();
                // console.log('[submitMembership] redirecting to:', data.checkout_url);
                // window.location.href = data.checkout_url;
                if (data.requires_action) {
                    setOverlayStep(2, 'Additional verification required…', 'Please complete the card authentication prompt to finish your payment.');
                    const authResult = await _stripe.confirmCardPayment(data.client_secret);

                    if (authResult.error) {
                        throw new Error(authResult.error.message || 'Card authentication was not completed.');
                    }

                    if (!authResult.paymentIntent || authResult.paymentIntent.status !== 'succeeded') {
                        throw new Error('Card authentication did not complete successfully.');
                    }

                    setOverlayStep(2, 'Finalizing your registration…', 'We are confirming your payment and completing your membership.');
                    await finalizeStripePayment(authResult.paymentIntent.id, csrfToken);
                }

                if(data.success){
                    setOverlayStep(2, 'Registration complete!', 'Thank you for joining ISGH. You will receive a confirmation email shortly.');
                    stopTimer();
                    window.setTimeout(() => {
                        if (overlay) overlay.classList.remove('visible');
                        window.location.href = '/membership/success/' + data.pending_id;
                    }, 4000);
                }

            } catch (err) {
                console.error('[submitMembership] error:', err);
                stopTimer();
                if (overlay) overlay.classList.remove('visible');
                if (btn) btn.disabled = false;
                if (btnLabel) btnLabel.innerHTML = cfg?.submitLabel || 'Complete Registration';
                showSubmitError(err.message);
            }
        }

        const state = "TX"; // future-proof (can be dynamic later)
        function fetchCities(stateCode) {
            return new Promise((resolve) => {
                setTimeout(() => {
                    const data = {
                        TX: [
                            "Alvin",
                            "Angleton",
                            "Atascocita",
                            "Bacliff",
                            "Baytown",
                            "Bellaire",
                            "Brookshire",
                            "Channelview",
                            "Cinco Ranch",
                            "Conroe",
                            "Cypress",
                            "Deer Park",
                            "Dickinson",
                            "Fresno",
                            "Friendswood",
                            "Fulshear",
                            "Galveston",
                            "Hempstead",
                            "Hockley",
                            "Houston",
                            "Humble",
                            "Jersey Village",
                            "Katy",
                            "Kemah",
                            "Kingwood",
                            "La Marque",
                            "La Porte",
                            "Lake Jackson",
                            "League City",
                            "Magnolia",
                            "Manvel",
                            "Meadows Place",
                            "Missouri City",
                            "Montgomery",
                            "Needville",
                            "Pasadena",
                            "Pearland",
                            "Porter",
                            "Richmond",
                            "Rosharon",
                            "Rosenberg",
                            "Seabrook",
                            "Sienna",
                            "Simonton",
                            "Spring",
                            "Stafford",
                            "Sugar Land",
                            "Texas City",
                            "The Woodlands",
                            "Tomball",
                            "Waller",
                            "Webster",
                            "West University Place"
                        ]
                    };
                    resolve(data[stateCode] || []);
                }, 300);
            });
        }

        function initCityDropdowns() {
            const stateSelects = document.querySelectorAll("select[id$='_state']");

            stateSelects.forEach(stateSelect => {
                const prefix = stateSelect.id.replace("_state", "");
                const citySelect = document.getElementById(prefix + "_city");

                if (!citySelect) return;

                // load cities on page load
                loadCitiesFor(prefix);

                // reload when state changes
                stateSelect.addEventListener("change", () => {
                    loadCitiesFor(prefix);
                });
            });
        }

        function loadCitiesFor(prefix, onLoad) {
            const stateSelect = document.getElementById(prefix + "_state");
            const citySelect = document.getElementById(prefix + "_city");

            if (!stateSelect || !citySelect) return;

            const state = stateSelect.value;
            const selectedCity = citySelect.value;

            citySelect.innerHTML = `<option>Loading cities...</option>`;
            citySelect.disabled = true;

            fetchCities(state).then(cities => {
                citySelect.innerHTML = `<option value="">Select City</option>`;

                cities.forEach(city => {
                    const opt = document.createElement("option");
                    opt.value = city;
                    opt.textContent = city;
                    citySelect.appendChild(opt);
                });

                if (selectedCity && cities.includes(selectedCity)) {
                    citySelect.value = selectedCity;
                }

                citySelect.disabled = false;
                if (onLoad) onLoad();
            });
        }

        async function finalizeStripePayment(paymentIntentId, csrfToken) {
            const res = await fetch('/membership/finalize-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ payment_intent_id: paymentIntentId }),
            });

            const rawText = await res.text();
            let data;
            try { data = JSON.parse(rawText); }
            catch (parseErr) {
                throw new Error('Server returned an unexpected finalize response (HTTP ' + res.status + '). Check Laravel logs.');
            }

            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Payment was authenticated, but finalization failed.');
            }

            return data;
        }

        // (city dropdowns initialized in DOMContentLoaded)

        function restrictPhoneInput(input) {
            // Remove all non-digits
            let digits = input.value.replace(/\D/g, "");

            // Limit to 10 digits ONLY
            digits = digits.slice(0, 10);

            // Format
            let formatted = "";
            if (digits.length > 0) {
                formatted = "(" + digits.substring(0, 3);
            }
            if (digits.length >= 4) {
                formatted += ") " + digits.substring(3, 6);
            }
            if (digits.length >= 7) {
                formatted += "-" + digits.substring(6, 10);
            }

            input.value = formatted;
        }

        // ─── CONFIRM & CONTINUE ───────────────────────────────────────────────────
        const PRIMARY_REQUIRED_IDS = [
            'uni_email', 'uni_phone', 'uni_first_name', 'uni_last_name',
            'uni_dob', 'uni_txdl', 'uni_street', 'uni_city', 'uni_zip'
        ];

        function isPrimaryValid() {
            for (const id of PRIMARY_REQUIRED_IDS) {
                const el = document.getElementById(id);
                if (!el) continue;
                const val = (el.tagName === 'SELECT' ? el.value : el.value.trim());
                if (!val || val === '') return false;
                // Phone: need 10 digits
                if (id === 'uni_phone' && el.value.replace(/\D/g, '').length < 10) return false;
                // DOB: MM/DD/YYYY format
                if (id === 'uni_dob' && !/^\d{2}\/\d{2}\/\d{4}$/.test(el.value.trim())) return false;
            }
            // ZIP must have resolved to a valid ISGH service area
            if (!_zipState['uni']?.valid) return false;
            // Block if the primary phone has an active validation error (duplicate / WA-registered / pending check)
            const _pEl = document.getElementById('uni_phone');
            if (_pEl && _pEl._hasValidationError === true) return false;
            return true;
        }

        function updateConfirmBtn() {
            const btn = document.getElementById('uni_confirm_btn');
            if (!btn) return;
            if (isPrimaryValid()) {
                btn.disabled = false;
                btn.style.background = 'linear-gradient(135deg, #0d7a55 0%, #10b981 100%)';
                btn.style.color = '#fff';
                btn.style.cursor = 'pointer';
            } else {
                btn.disabled = true;
                btn.style.background = '#d1d5db';
                btn.style.color = '#9ca3af';
                btn.style.cursor = 'not-allowed';
            }
        }

        function attachConfirmWatchers() {
            PRIMARY_REQUIRED_IDS.forEach(id => {
                const el = document.getElementById(id);
                if (!el || el._confirmWatchAttached) return;
                el._confirmWatchAttached = true;
                el.addEventListener('input', updateConfirmBtn);
                el.addEventListener('change', updateConfirmBtn);
                el.addEventListener('blur', updateConfirmBtn);
            });
        }

        function disablePrimaryFields() {
            PRIMARY_REQUIRED_IDS.push('uni_middle_name');
            PRIMARY_REQUIRED_IDS.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                el.disabled = true;
                el.style.background = '#f3f4f6';
                el.style.cursor = 'not-allowed';
                el.style.opacity = '0.7';
            });
        }

        function confirmAndContinue() {
            if (!isPrimaryValid()) return;

            // Populate the review popup with current field values
            const v = id => document.getElementById(id)?.value.trim() ?? '';
            document.getElementById('prc_name').textContent  = `${v('uni_first_name')} ${v('uni_last_name')}`;
            document.getElementById('prc_email').textContent = v('uni_email');
            document.getElementById('prc_phone').textContent = v('uni_phone');
            document.getElementById('prc_dob').textContent   = v('uni_dob');
            document.getElementById('prc_txdl').textContent  = v('uni_txdl');
            document.getElementById('prc_addr').textContent  =
                `${v('uni_street')}, ${document.getElementById('uni_city')?.value ?? ''}, ${document.getElementById('uni_state')?.value ?? ''} ${v('uni_zip')}`;

            document.getElementById('primaryReviewOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePrimaryReview() {
            document.getElementById('primaryReviewOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }

        function proceedPrimaryCheck() {
            closePrimaryReview();

            const btn   = document.getElementById('uni_confirm_btn');
            const errEl = document.getElementById('uni_duplicate_error');
            errEl.style.display = 'none';

            const origLabel = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Checking…';
            btn.style.cursor = 'not-allowed';

            const email     = document.getElementById('uni_email').value.trim();
            const firstName = document.getElementById('uni_first_name').value.trim();
            const lastName  = document.getElementById('uni_last_name').value.trim();
            const phone     = document.getElementById('uni_phone').value.trim();
            const dateOfBirth = document.getElementById('uni_dob').value.trim();

            fetch('{{ route("membership.verify") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ first_name: firstName, last_name: lastName, date_of_birth: dateOfBirth }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Duplicate found — show error, disable rest of form
                    errEl.style.display = 'block';
                    const restOfForm = document.getElementById('uni_rest_of_form');
                    if (restOfForm) {
                        restOfForm.style.display = 'none';
                        restOfForm.querySelectorAll('input, select, textarea, button').forEach(el => {
                            el.disabled = true;
                            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                                el.style.background = '#f3f4f6';
                                el.style.cursor = 'not-allowed';
                            }
                        });
                    }
                    btn.disabled = false;
                    btn.innerHTML = origLabel;
                    btn.style.background = 'linear-gradient(135deg, #0d7a55 0%, #10b981 100%)';
                    btn.style.color = '#fff';
                    btn.style.cursor = 'pointer';
                } else {
                    // Not found — hide confirm wrap, reveal rest of form
                    disablePrimaryFields();
                    document.getElementById('uni_confirm_wrap').style.display = 'none';
                    const restOfForm = document.getElementById('uni_rest_of_form');
                    if (restOfForm) {
                        restOfForm.style.display = 'block';
                        restOfForm.querySelectorAll('input, select, textarea, button').forEach(el => {
                            el.disabled = false;
                            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                                el.style.background = '';
                                el.style.cursor = '';
                            }
                        });
                    }
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = origLabel;
                btn.style.background = 'linear-gradient(135deg, #0d7a55 0%, #10b981 100%)';
                btn.style.color = '#fff';
                btn.style.cursor = 'pointer';
            });
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

    <!-- Checkomatic Spouse Disclaimer -->
    <div id="checkomaticSpouseModal" class="confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="checkomaticSpouseTitle" onclick="if (event.target === this) closeCheckomaticSpouseDisclaimer()">
        <div class="confirm-box">
            <div class="confirm-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8v4"/><path d="M12 16h.01"/><path d="M10.29 3.86l-8.43 14.5A2 2 0 0 0 3.58 21h16.84a2 2 0 0 0 1.72-2.64l-8.43-14.5a2 2 0 0 0-3.44 0z"/>
                </svg>
            </div>
            <p class="confirm-title" id="checkomaticSpouseTitle">Checkomatic Reminder</p>
            <p class="confirm-body" style="margin-bottom:0.9rem;">Before adding a spouse, please review the payment reminder below.</p>
            <div class="checkomatic-spouse-note">
                Checkomatic dues totaling $20 per member must be paid by June 30th.
            </div>
            <div class="confirm-actions" style="margin-top:1.25rem;">
                <button class="confirm-btn-yes" onclick="confirmCheckomaticSpouseDisclaimer()">Got It</button>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions Modal -->
    <div id="termsModal"
        style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;">
        <div
            style="background:#fff;border-radius:16px;max-width:560px;width:92%;padding:2rem 2rem 1.5rem;box-shadow:0 24px 64px rgba(0,0,0,0.25);position:relative;max-height:90vh;overflow-y:auto;">

            <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.25rem;">
                <div
                    style="width:36px;height:36px;border-radius:50%;background:#1a4a2e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4" />
                        <path d="M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z" />
                    </svg>
                </div>
                <h2 style="font-size:1.2rem;font-weight:700;color:#1a4a2e;margin:0;">
                    ISGH Membership — Terms &amp; Conditions
                </h2>
                <button onclick="closeTermsModal()"
                    style="margin-left:auto;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#6b7280;line-height:1;">&times;</button>
            </div>

            <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:1.5rem;">

                <!-- ITEM -->
                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">1.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        I agree to abide by the <strong>Constitution, Bylaws, and policies</strong> of ISGH and
                        acknowledge that membership is subject to approval and compliance.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">2.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        I confirm that all information provided is <strong>accurate and complete</strong>. Incorrect or
                        incomplete information may affect eligibility.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">3.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        Membership dues are <strong>annual (Jan 1 – Dec 31)</strong>, non-refundable, and must be paid
                        through approved methods.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">4.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        Voting eligibility requires:
                        <br>• Age 18+
                        <br>• Completed application
                        <br>• Dues paid by June 30
                        <br>• Participation/volunteer requirements
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">5.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        Family memberships apply to <strong>husband and spouse only</strong>, and all required spouse
                        information must be provided.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">6.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        ISGH reserves the right to <strong>verify, approve, delay, or decline</strong> applications.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">7.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        I am responsible for maintaining my membership. Failure to renew by June 30 may result in
                        <strong>loss of voting rights</strong>.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">8.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        I agree to maintain <strong>respectful conduct</strong> and uphold ISGH values. Violations may
                        result in suspension.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">9.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        ISGH follows a strict <strong>Privacy Policy</strong> and protects personal information.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">10.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        I consent to receive <strong>communications</strong> including updates, elections, and events.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">11.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        ISGH or affiliated centers may contact me regarding <strong>membership-related matters</strong>.
                    </p>
                </div>

                <div
                    style="display:flex;gap:10px;align-items:flex-start;padding:12px;background:#f8fdf9;border-radius:8px;border-left:3px solid #1a4a2e;">
                    <span style="color:#1a4a2e;font-weight:700;font-size:1.1rem;">12.</span>
                    <p style="margin:0;color:#374151;font-size:14px;line-height:1.6;">
                        I confirm that I have read, understood, and agree to all <strong>Terms &amp;
                            Conditions</strong>.
                    </p>
                </div>

            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button onclick="closeTermsModal()"
                    style="padding:10px 20px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#6b7280;font-size:14px;cursor:pointer;">
                    Cancel
                </button>
                <button onclick="acceptTermsAndClose()"
                    style="padding:10px 24px;border:none;border-radius:8px;background:#1a4a2e;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">
                    I Accept All Terms
                </button>
            </div>

        </div>
    </div>

    <div class="page-outer-wrapper" style="background:rgba(248,248,248,1);">

        <!-- ══════════ HEADER (UNCHANGED) ══════════ -->
        <header class="w-full pt-2 sm:pt-4 relative z-50 px-3 sm:px-8 md:px-16 lg:px-24">
            <div class="flex items-center gap-3">
            <div class="hidden lg:block flex-shrink-0">
                <div
                    class="w-14 h-14 rounded-full border border-[#c8a84b] flex items-center justify-center bg-[#1a4a2e] shadow-lg">
                    <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="w-10 h-10 object-contain">
                </div>
            </div>
            <nav class="hidden lg:flex navbar-glass rounded-full pl-8 pr-2 py-2 items-center gap-8 ml-auto">
                <div class="hidden lg:flex items-center gap-7">
                    <a href="{{ route('home') }}"
                        class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Home</a>
                    <a href="#"
                        class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Centers</a>
                    <a href="#"
                        class="text-white text-[15px] font-medium hover:text-gray-300 transition-colors">Donate</a>
                    <a href="{{ route('join') }}"
                        class="text-white/75 text-[15px] font-medium hover:text-gray-300 transition-colors">Become a
                        Member</a>
                    <a href="{{ route('membership-verification') }}"
                        class="text-white/75 text-[15px] font-medium hover:text-gray-300 transition-colors">Verify
                        Membership Status</a>
                </div>
                <div class="flex items-center gap-3">
                    <a href="https://isgh.wildapricot.org/Sys/Login" target="_blank" rel="noopener noreferrer"
                        class="bg-white/20 hover:bg-white/30 text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors inline-block text-center">Sign
                        in</a>
                    <a href="{{ route('join') }}" style="background:#00d084;"
                        class="hover:bg-[#00b870] text-white text-[15px] font-semibold px-6 py-2.5 rounded-full transition-colors shadow-md inline-block text-center">Join
                        Now</a>
                </div>
            </nav>

            <div class="w-full lg:hidden">
                <div class="flex w-full items-center justify-between rounded-full border-[8px] border-white bg-[#1c1c1c] px-4 py-3 shadow-[0_12px_30px_rgba(0,0,0,0.18)] min-h-[72px]">
                    <a href="{{ route('home') }}" class="shrink-0" aria-label="ISGH Home">
                        <div class="w-12 h-12 rounded-full border-2 border-[#c8a84b] flex items-center justify-center bg-[#1a4a2e] shadow-lg">
                            <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo" class="w-8 h-8 object-contain">
                        </div>
                    </a>
                    <button class="flex h-11 w-11 items-center justify-center rounded-full"
                        onclick="openMobileMenu()" aria-label="Open menu">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
            </div>
        </header>

        <!-- ══════════ HERO (UNCHANGED) ══════════ -->
        <section
            class="hero-bg min-h-[260px] sm:min-h-[420px] flex items-center justify-center pt-4 sm:pt-8 pb-14 sm:pb-16 px-4"
            style="border-bottom-left-radius:50px;border-bottom-right-radius:50px;position:relative;top:-86px;">
            <div class="relative z-10 flex flex-col items-center text-center max-w-3xl mx-auto gap-4 sm:gap-6 mt-6 sm:mt-16">
                <h1
                    class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white drop-shadow-md tracking-tight">
                    Join ISGH</h1>
                <p class="text-white/90 text-sm sm:text-base leading-relaxed max-w-lg drop-shadow-sm">
                    Your membership supports our Masajid, provides free healthcare at Shifa Clinics, and empowers our
                    youth through education. Choose the category that best fits your family and join our legacy of
                    service.
                </p>
                <div
                    class="inline-flex flex-col sm:flex-row items-center gap-1 sm:gap-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl sm:rounded-full py-2 px-5 sm:px-6 shadow-lg">
                    <div class="flex items-center gap-1 text-yellow-400 text-base sm:text-lg">★ ★ ★ ★ ★</div>
                    <span class="text-white text-xs sm:text-sm font-medium" style="font-family:'SF Pro regular';">Join thousands
                        active members across Greater Houston.</span>
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
                        <p>Join our mission to provide immediate relief to those affected by natural disasters and
                            poverty. Your generosity provides a lifeline to families in their most vulnerable moments.
                        </p>
                    </div>
                    <div class="side-item">
                        <img src="{{ asset('images/molvi.png') }}" alt="Chaplaincy">
                        <h3>Chaplaincy</h3>
                        <p>ISGH's Chaplaincy Services is dedicated to offering services to Muslim chaplains through
                            endorsement, education & training, and leadership development.</p>
                    </div>
                    <div class="side-item">
                        <img src="{{ asset('images/education_forum.png') }}" alt="Education Forum">
                        <h3>Education Forum</h3>
                        <p>Provide training & resources for students to realize their full potential and contribute to
                            society. Help us support educational excellence and lifelong learning.</p>
                    </div>
                    <div class="side-item">
                        <img src="{{ asset('images/molvi.png') }}" alt="Youth Programs">
                        <h3>Youth Programs</h3>
                        <p>The ISGH I-YOUTH program provides programming, leadership, and support with a sustainable
                            focus on our youth and the future of the Muslim community.</p>
                    </div>
                    <div class="side-item">
                        <img src="{{ asset('images/kronic_Academy.png') }}" alt="Quran Academy">
                        <h3>Quran Academy</h3>
                        <p>Ensure the teaching of the Holy Quran under our expert religious instructors. Give the gift
                            of Islamic education to our youth and family.</p>
                    </div>
                </div>

                <!-- ── CENTER CARD ── -->
                <div class="center-card">

                    <!-- STEP 1: Choose Membership Type -->
                    <div class="step-indicator">
                        <div class="step-number">1</div>
                        <h2 class="form-section-title">Choose Membership Type</h2>
                        <p class="step-subtitle">Please select the membership category that best fits your household.
                        </p>
                    </div>

                    <div class="membership-selector" style="margin-top:1.5rem;">
                        <span class="sel-label">Choose Membership Type</span>
                        <select id="membershipSelector" onchange="toggleMembershipForm()">
                            <option value="">— Select a Membership Type —</option>
                            <!-- <option value="family">Family Membership (Primary and Spouse only) — $40/year</option> -->
                            <!-- <option value="individual">Individual Membership — $25/year</option> -->
                            <!-- <option value="flat">Flat Membership — $20/year</option>
            <option value="checkomatic_family">Checkomatic Membership (Primary and Spouse only) — $10/month</option>
            <option value="checkomatic_individual">Checkomatic Membership Individual — $10/month</option>
            <option value="lifetime_family">Lifetime Membership (Family - Primary and Spouse) — $1500/lifetime</option>
            <option value="lifetime_individual">Lifetime Membership (Individual) — $1000/lifetime</option> -->
                            <option value="flat">Individual Membership</option>
                            <option value="checkomatic_individual">Checkomatic Membership</option>
                            <option value="lifetime_individual">Lifetime Membership</option>
                        </select>
                    </div>

                    <!-- ════════════════════════════════════════
             UNIFIED MEMBERSHIP SECTION
        ════════════════════════════════════════ -->
                    <div id="unifiedMembershipSection" style="display:none;">

                        <!-- Dynamic Banner -->
                        <div class="membership-banner banner-flat" id="uni_banner">
                          <svg class="order-summary-rings" viewBox="0 0 67 161" fill="none"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path
                            d="M8.63155 -5.81175C26.517 2.41 30.1972 13.7315 33.9435 36.9093C33.9435 36.9093 23.8466 71.8043 5.61968 81.3882C-14.405 91.9173 -43.217 96.831 -52.0625 76.0078C-59.7778 57.8448 -45.5249 41.8524 -29.0193 31.0363C-6.08354 16.0066 19.5073 25.3131 40.0966 43.4245C54.4146 56.0194 60.4147 69.5516 59.7683 85.2666C59.122 100.982 44.6732 126.268 44.6732 126.268C44.6732 126.268 30.3991 145.99 17.1464 151.096C4.63453 155.916 -17.1701 152.197 -17.1701 152.197"
                            stroke="white" stroke-width="13.0552" />
                    </svg>
                            <div class="banner-main-row">
                            <div class="banner-left">
                                <p class="banner-title" id="uni_banner_title"></p>
                                <p class="banner-subtitle" id="uni_banner_subtitle"></p>
                                <div class="banner-badges">
                                    <span class="banner-badge">✔ Member Discounts</span>
                                    <span class="banner-badge">✔ Prayer Facilities</span>
                                    <span class="banner-badge">✔ Community Events</span>
                                    <span class="banner-badge">✔ Voting Rights</span>
                                    <span class="banner-badge">✔ Each Member has separate login</span>
                                    <span class="banner-badge">✔ Funeral Service Discount</span>
                                </div>
                            </div>
                            <div class="banner-price">
                                <p class="price-amount" id="uni_price_amount"></p>
                                <p class="price-period" id="uni_price_period"></p>
                            </div>
                            </div>

                        <!-- CHECKOMATIC AMOUNT INPUT -->
                        <div id="uni_checkomatic_amount_wrap" class="checkomatic-amount-shell" style="display:none;">
                            <label class="checkomatic-amount-label">
                                Monthly Amount <span style="color:#fff;">*</span>
                                <span class="checkomatic-amount-hint" id="uni_checkomatic_amount_hint">(minimum $10/month)</span>
                            </label>
                            <div class="checkomatic-amount-field">
                                <span class="checkomatic-amount-prefix">$</span>
                                <input type="number" id="uni_checkomatic_amount" min="10" value="10" step="1"
                                    class="checkomatic-amount-input" oninput="updateCheckomaticNote()">
                            </div>
                            <p id="uni_checkomatic_error" class="checkomatic-amount-error" style="display:none;"></p>
                            <p id="uni_checkomatic_note" class="checkomatic-amount-note">
                                You will be charged $10.00/month
                            </p>
                        </div>
                        </div>

                        <div id="uni_checkomatic_warning" class="checkomatic-warning">
                            <p>To qualify as a voting member for the current year, a minimum membership contribution of $20/person must be completed by June 30.</p>
                        </div>

                        <!-- STEP 2: Primary Information -->
                        <div class="step-indicator">
                            <div class="step-number">2</div>
                            <h3 class="form-section-title">Primary Information</h3>
                        </div>
                        <div style="text-align:center;">
                            <div onclick="openIdScan('uni')"
                                style="display:inline-flex;align-items:center;gap:16px;padding:1px 9px;border-radius:999px;font-size:12px;font-weight:500;color:#eaf7f3;margin-bottom:28px;cursor:pointer;background:linear-gradient(90deg,#0f5c45 0%,#2f8f6b 50%,#55c59a 100%);box-shadow:0 6px 16px rgba(0,0,0,0.15);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#eaf7f3" stroke-width="2.6"
                                    style="width:34px;height:34px;">
                                    <path d="M8 4H6a2 2 0 0 0-2 2v2" stroke-linecap="round" />
                                    <path d="M16 4h2a2 2 0 0 1 2 2v2" stroke-linecap="round" />
                                    <path d="M8 20H6a2 2 0 0 1-2-2v-2" stroke-linecap="round" />
                                    <path d="M16 20h2a2 2 0 0 0 2-2v-2" stroke-linecap="round" />
                                </svg>
                                <span style="letter-spacing:0.3px;">Scan ID Card</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="#f7c873" stroke-width="2.4"
                                    style="width:30px;height:30px;">
                                    <path d="M12 3l2.2 5.2L20 10l-5.8 1.8L12 17l-2.2-5.2L4 10l5.8-1.8L12 3z"
                                        stroke-linejoin="round" />
                                    <path d="M19 15l.8 1.8L22 18l-2.2.8L19 21l-.8-1.8L16 18l2.2-.8L19 15z"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>

                        <div class="fields-stack">
                            <div class="field">
                                <label>Email Address <span>*</span></label>
                                <input type="email" id="uni_email" placeholder="ahmad@example.com"
                                    value="{{ $verifiedEmail }}" readonly
                                    style="background:#f3f4f6;cursor:not-allowed;">
                                <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <path
                                            d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                        <polyline points="22,6 12,13 2,6" />
                                    </svg></div>
                            </div>
                            <div class="field">
                                <label>Phone Number <span>*</span></label>
                                <input type="text" id="uni_phone" placeholder="e.g. (832) 555-0100"
                                    oninput="restrictPhoneInput(this)" onblur="validateUsPhone(this)">
                                <div id="uni_phone_msg" class="phone-msg"></div>
                            </div>
                            <div class="field">
                                <label>First Name <span>*</span></label>
                                <input type="text" id="uni_first_name" placeholder="Ahmad">
                                <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg></div>
                            </div>
                            <div class="field">
                                <label>Middle Name</label>
                                <input type="text" id="uni_middle_name" placeholder="Middle Name (Optional)">
                            </div>
                            <div class="field">
                                <label>Last Name <span>*</span></label>
                                <input type="text" id="uni_last_name" placeholder="Ali">
                                <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg></div>
                            </div>
                            <div class="field">
                                <label>Date of Birth <span>*</span></label>
                                <input type="text" id="uni_dob" placeholder="MM/DD/YYYY">
                                <div class="dob-msg"></div>
                                <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8" y1="2" x2="8" y2="6" />
                                        <line x1="3" y1="10" x2="21" y2="10" />
                                    </svg></div>
                            </div>
                            <div class="field">
                                <label>TX DL # or ID Card # <span>*</span></label>
                                <input type="text" id="uni_txdl" placeholder="e.g. TX7234578">
                                <div class="field-icon"><svg width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="5" width="20" height="14" rx="2" />
                                        <line x1="2" y1="10" x2="22" y2="10" />
                                    </svg></div>
                            </div>
                            <div class="field">
                                <label>Street Address <span>*</span></label>
                                <input type="text" id="uni_street" placeholder="123 Main Street"
                                    oninput="onPrimaryAddressChange()">
                            </div>
                            <div style="display:flex;margin-top:-5px;">
                                <div style="flex:1; display:flex; flex-direction:column;">
                                    <label style="font-size:12px; margin-left:12px;">State <span
                                            style="color:#ef4444;">*</span></label>
                                    <select id="uni_state" onchange="onPrimaryAddressChange()" disabled
                                        style="padding:10px; font-size:14px; border-radius:8px; border:1px solid #d1d5db; background:#f3f4f6; cursor:not-allowed;">
                                        <option value="TX" selected>Texas</option>
                                    </select>
                                </div>
                                <div style="flex:1; display:flex; flex-direction:column;">
                                    <label style="font-size:12px; margin-left:12px;">City <span
                                            style="color:#ef4444;">*</span></label>
                                    <select id="uni_city" onchange="onPrimaryAddressChange()"
                                        style="padding:10px; margin-left:5px; font-size:14px; border-radius:8px; border:1px solid #d1d5db;">
                                        <option value="" disabled selected>City</option>
                                    </select>
                                </div>
                            </div>
                            <div class="field">
                                <label>ZIP Code <span>*</span></label>
                                <input type="text" id="uni_zip" placeholder="77001"
                                    onblur="validateZip(this,'uni')" oninput="onPrimaryAddressChange()">
                                <div id="uni_zip_msg" class="zip-msg"></div>
                            </div>
                            <div class="field zone-field" id="uni_center_field" style="display:none;">
                                <label>Center / Zone</label>
                                <div id="uni_center_display"></div>
                            </div>
                            <div class="field" id="uni_donation_type_field" style="display:none;">
                                <label for="uni_donation_type">Donation Type <span style="color:red;">*</span></label>
                                <select id="uni_donation_type" name="donation_type">
                                    <option value="">— Select Donation Type —</option>
                                </select>
                            </div>
                        </div>

                        <!-- CONFIRM & CONTINUE BUTTON -->
                        <div id="uni_confirm_wrap" style="margin-top:1.25rem;">
                            <div id="uni_duplicate_error" style="display:none;background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;border-radius:0.6rem;padding:0.7rem 1rem;font-size:0.85rem;margin-bottom:0.75rem;font-weight:600;">
                                ⚠ Duplicate record found. A membership already exists for this email/name. Please contact ISGH support if you believe this is an error.
                            </div>
                            <button id="uni_confirm_btn" type="button" onclick="confirmAndContinue()"
                                disabled
                                style="width:100%;padding:0.85rem 1.5rem;border:none;border-radius:0.85rem;font-size:0.92rem;font-weight:700;font-family:inherit;cursor:not-allowed;transition:all 0.2s;background:#d1d5db;color:#9ca3af;">
                                Confirm &amp; Continue
                            </button>
                        </div>

                        <!-- REST OF FORM (hidden until primary is confirmed) -->
                        <div id="uni_rest_of_form" style="display:none;">

                        <div class="section-divider"></div>

                        <!-- SPOUSE SECTION (shown for family types) -->
                        <div id="uni_spouse_section" style="display:none;">
                            <div class="step-indicator">
                                <div class="step-number">3</div>
                                <h3 class="form-section-title">Spouse Information</h3>
                            </div>
                            <button type="button" id="uni_add_spouse_btn" onclick="showSpouseForm()"
                                class="btn-add-member" style="display:none;">
                                + Add Your Spouse
                            </button>
                            <div id="uni_spouse_form_wrap" style="display:none;">
                            <div id="uni_spouses_container">
                                <div class="spouse-block" id="uni_spouse_block_0">
                                    <div class="spouse-block-header dependent-card-header">
                                        <div class="dependent-card-meta">
                                            <div class="member-tag">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                Spouse 1
                                            </div>
                                        </div>
                                        <div class="dependent-scan-btn" onclick="openIdScan('uni_spouse_0')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="#eaf7f3" stroke-width="2.6" style="width:26px;height:26px;"><path d="M8 4H6a2 2 0 0 0-2 2v2" stroke-linecap="round"/><path d="M16 4h2a2 2 0 0 1 2 2v2" stroke-linecap="round"/><path d="M8 20H6a2 2 0 0 1-2-2v-2" stroke-linecap="round"/><path d="M16 20h2a2 2 0 0 0 2-2v-2" stroke-linecap="round"/></svg>
                                            <span style="letter-spacing:0.3px;">Scan Your ID</span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="#f7c873" stroke-width="2.4" style="width:22px;height:22px;"><path d="M12 3l2.2 5.2L20 10l-5.8 1.8L12 17l-2.2-5.2L4 10l5.8-1.8L12 3z" stroke-linejoin="round"/></svg>
                                        </div>
                                        <div class="dependent-card-actions">
                                            <button type="button" class="btn-remove-block" onclick="removeSpouseForm()" title="Remove Spouse">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="fields-stack">
                                        <div class="field"><label>Email Address</label><input type="email"
                                                id="uni_spouse_0_email" placeholder="spouse@example.com">
                                            <div id="uni_spouse_0_email_msg" class="email-msg"></div>
                                        </div>
                                        <div class="field"><label>Phone Number <span>*</span></label><input
                                                type="text" id="uni_spouse_0_phone"
                                                placeholder="e.g. (832) 555-0100" oninput="restrictPhoneInput(this)"
                                                onblur="validateUsPhone(this)">
                                            <div id="uni_spouse_0_phone_msg" class="phone-msg"></div>
                                        </div>
                                        <div class="field"><label>First Name <span>*</span></label><input
                                                type="text" id="uni_spouse_0_first_name" placeholder="Fatima">
                                        </div>
                                        <div class="field"><label>Middle Name</label><input type="text"
                                                id="uni_spouse_0_middle_name" placeholder="Middle Name (Optional)">
                                        </div>
                                        <div class="field"><label>Last Name <span>*</span></label><input
                                                type="text" id="uni_spouse_0_last_name" placeholder="Ali"></div>
                                        <div class="field"><label>Date of Birth <span>*</span></label><input
                                                type="text" id="uni_spouse_0_dob" placeholder="MM/DD/YYYY">
                                            <div class="dob-msg"></div>
                                        </div>
                                        <div class="field"><label>TX DL # or ID Card # <span>*</span></label><input
                                                type="text" id="uni_spouse_0_txdl" placeholder="e.g. TX7234578">
                                        </div>
                                        <div class="dependent-address-summary">
                                            <span class="dependent-address-summary-label">Address</span>
                                            <div class="dependent-address-summary-value" id="uni_spouse_0_address_summary">Same as primary member address</div>
                                        </div>
                                        <div class="dependent-address-fields">
                                        <div class="field"><label>Street Address</label><input type="text"
                                                id="uni_spouse_0_street" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
                                        <div style="display:flex;margin-top:-5px;">
                                            <div style="flex:1; display:flex; flex-direction:column;">
                                                <label style="font-size:12px; margin-left:12px;">State</label>
                                                <select id="uni_spouse_0_state" disabled
                                                    style="padding:10px; font-size:14px; border-radius:8px; border:1px solid #d1d5db; background:#f3f4f6;">
                                                    <option value="TX" selected>Texas</option>
                                                </select>
                                            </div>
                                            <div style="flex:1; display:flex; flex-direction:column;">
                                                <label style="font-size:12px; margin-left:12px;">City</label>
                                                <select id="uni_spouse_0_city"
                                                    style="padding:10px; margin-left:5px; font-size:14px; border-radius:8px; border:1px solid #d1d5db;">
                                                    <option value="" disabled selected>City</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="field"><label>ZIP Code</label><input type="text"
                                                id="uni_spouse_0_zip" placeholder="Auto-filled from primary" readonly style="background:#f3f4f6;cursor:not-allowed;"></div>
                                        <div class="field zone-field" id="uni_spouse_0_center_field" style="display:none;">
                                            <label>Center / Zone</label>
                                            <div id="uni_spouse_0_center_display"></div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            </div>{{-- /uni_spouse_form_wrap --}}
                        </div>

                        <!-- FLAT FAMILY MEMBERS SECTION (shown for annual membership) -->
                        <div id="uni_flat_members_section" style="display:none;">
                            <div class="step-indicator">
                                <div class="step-number">3</div>
                                <h3 class="form-section-title">Pay for Family Members</h3>
                            </div>
                            <div class="flat-pay-note">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" y1="8" x2="12" y2="12" />
                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                </svg>
                                Pay for your family members who are 18 and over and reside at the same address.
                            </div>
                            <div id="flat_members_container"></div>
                            <button class="btn-add-member" type="button" onclick="addFlatMemberBlock()">
                                <div
                                    style="width:22px;height:22px;border-radius:50%;border:2px dashed #d1d5db;display:flex;align-items:center;justify-content:center;font-size:0.9rem;line-height:1;">
                                    +</div>
                                Add Another Family Member (Optional)
                            </button>
                        </div>
                        <div class="section-divider"></div>
                        <!-- STRIPE CARD ELEMENT -->
        <div class="stripe-card-section">
            <p class="stripe-card-label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Card Details
            </p>
            <div class="field stripe-card-name-field" style="margin-top:1.5rem">
                <label for="uni_cardholder_name">Name on Card <span>*</span></label>
                <input type="text" id="uni_cardholder_name" placeholder="Enter the name shown on your card" autocomplete="cc-name">
            </div>
            <div id="stripe-card-element"></div>
            <div id="stripe-card-error" role="alert"></div>
        </div>

                        <!-- ORDER SUMMARY (dynamic) -->
                        <div class="order-summary">
                            {{-- <img src="{{ asset('images/order-summary-bg.png') }}" class="order-summary-bg-img" alt="" style="width:1200px"> --}}

                            <svg class="order-summary-rings" viewBox="0 0 67 161" fill="none"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path
                                    d="M8.63155 -5.81175C26.517 2.41 30.1972 13.7315 33.9435 36.9093C33.9435 36.9093 23.8466 71.8043 5.61968 81.3882C-14.405 91.9173 -43.217 96.831 -52.0625 76.0078C-59.7778 57.8448 -45.5249 41.8524 -29.0193 31.0363C-6.08354 16.0066 19.5073 25.3131 40.0966 43.4245C54.4146 56.0194 60.4147 69.5516 59.7683 85.2666C59.122 100.982 44.6732 126.268 44.6732 126.268C44.6732 126.268 30.3991 145.99 17.1464 151.096C4.63453 155.916 -17.1701 152.197 -17.1701 152.197"
                                    stroke="white" stroke-width="13.0552" />
                            </svg>
                            <p class="order-summary-inner-title" style="position:relative;z-index:1;">Order Summary
                            </p>
                            <div class="order-row"><span>Membership Type</span><span id="uni_order_type"></span></div>
                            <div class="order-row" id="uni_order_fee_row"><span id="uni_order_fee_label">Membership Fee</span><span
                                    id="uni_order_fee"></span></div>
                            <div class="order-row" id="uni_flat_count_row" style="display:none;"><span
                                    id="flat_member_count">Family Members (1)</span><span
                                    id="flat_fee_display">$20.00</span></div>
                            <div class="order-total">
                                <span class="order-total-label">Total</span>
                                <span class="order-total-amount" id="uni_order_total"></span>
                            </div>
                        </div>
                        <span id="uni_billing_note"
                            style="display:none;font-size:0.75rem;color:#6b7280;margin-top:0.4rem;"></span>

                        <div id="uni_auto_renewal_box" class="checkbox-box" style="margin-top:0.75rem;display:none;">
                            <input type="checkbox" id="uni_auto_renewal" class="custom-checkbox">
                            <label for="uni_auto_renewal">
                                <strong style="color:#111827;font-size:0.8rem;">Enable Auto-Renewal</strong><br>
                                <span style="font-size:0.73rem;color:#6b7280;">Automatically renew my membership each year so it never lapses. You can cancel anytime from your account.</span>
                            </label>
                        </div>

                        <div class="section-divider"></div>

                        <!-- TERMS & AGREEMENTS -->
                        <div class="step-indicator">
                            <div class="step-number">5</div>
                            <h3 class="form-section-title">Terms &amp; Agreements</h3>
                        </div>
                        <div class="terms-list flex flex-col gap-0">
                            <div class="checkbox-box">
                                <input type="checkbox" id="uni_terms" class="custom-checkbox">
                                <label for="uni_terms">I agree to the <a href="#"
                                        class="text-emerald-600 font-bold underline"
                                        onclick="openTermsModal(event)">Terms and Conditions</a> of the Islamic Society
                                    of Greater Houston.</label>
                            </div>
                        </div>

                        <div id="uni_submit_error" style="display:none;background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;border-radius:0.6rem;padding:0.7rem 1rem;font-size:0.82rem;margin-top:0.75rem;white-space:pre-line;"></div>
                        <button id="uni_submit_btn" class="btn-submit" type="button"
                            onclick="submitUnifiedMembership()" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            <span id="uni_submit_label">Complete Registration</span>
                        </button>
                        <p class="secure-note">🔒 Your payment information is secured with Stripe.</p>
                        </div>
                        <!-- /uni_rest_of_form -->
                    </div>
                    <!-- /unifiedMembershipSection -->


                    <!-- ════════════════════════════════════════
             DEFAULT LOCKED SECTION
        ════════════════════════════════════════ -->
                    <div id="defaultLockedSection" class="locked-section">
                        <div class="lock-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <h3>Select a Membership Type to Continue</h3>
                        <p>Personal information and payment fields will unlock once you choose a plan above.</p>
                        <div class="locked-steps">
                            <div class="locked-step">
                                <div class="locked-step-num">2</div>
                                <div class="locked-step-bar"></div>
                            </div>
                            <div class="locked-step">
                                <div class="locked-step-num">3</div>
                                <div class="locked-step-bar"></div>
                            </div>
                            <div class="locked-step">
                                <div class="locked-step-num">4</div>
                                <div class="locked-step-bar"></div>
                            </div>
                            <div class="locked-step">
                                <div class="locked-step-num">5</div>
                                <div class="locked-step-bar"></div>
                            </div>
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
                        <p>Invest in long-term projects like schools, water wells, and educational resources that
                            benefit generations. Your contribution earns continuous reward long after it's given.</p>
                    </div>
                    <div class="side-item">
                        <img src="{{ asset('images/latter.png') }}" alt="Zakat">
                        <h3>Zakat</h3>
                        <p>Zakat is one of the five pillars of Islam. Help us distribute Zakat to those who truly need
                            it and fulfill your religious obligation through ISGH's trusted channels.</p>
                    </div>
                    <div class="side-item">
                        <img src="{{ asset('images/mosque2.png') }}" alt="Masjid Maintenance Fund">
                        <h3>Masjid Maintenance / Fund</h3>
                        <p>Support daily operations, maintenance, and beautification of our facilities, ensuring a safe
                            and welcoming environment for every worshiper.</p>
                    </div>
                    <div class="side-item">
                        <img src="{{ asset('images/takecare.png') }}" alt="Ongoing Charity Fund">
                        <h3>Ongoing Charity / Fund</h3>
                        <p>Help sustain continuous charitable programs that serve our community throughout the year,
                            from Ramadan initiatives to emergency aid distributions.</p>
                    </div>
                    <div class="side-item">
                        <img src="{{ asset('images/matrimonial_services.png') }}" alt="Matrimonial Services">
                        <h3>Matrimonial Services</h3>
                        <p>Finding a life partner is a significant journey. ISGH is here to simplify that process and
                            guide you toward a blessed and community-centered marriage.</p>
                    </div>
                </div>

            </div>
            <!-- /membership-grid -->

            <!-- ── MOBILE HORIZONTAL SCROLL (all side items, shown only on mobile) ── -->
            <div class="mobile-side-scroll">
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/truck.png') }}" alt="Humanitarian Aid">
                    <h3>Humanitarian Aid</h3>
                    <p>Immediate relief for those affected by natural disasters and poverty.</p>
                </div>
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/molvi.png') }}" alt="Chaplaincy">
                    <h3>Chaplaincy</h3>
                    <p>Endorsement, education & training, and leadership for Muslim chaplains.</p>
                </div>
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/education_forum.png') }}" alt="Education Forum">
                    <h3>Education Forum</h3>
                    <p>Training & resources for students to realize their full potential.</p>
                </div>
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/molvi.png') }}" alt="Youth Programs">
                    <h3>Youth Programs</h3>
                    <p>Programming, leadership & support for ISGH youth and community.</p>
                </div>
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/kronic_Academy.png') }}" alt="Quran Academy">
                    <h3>Quran Academy</h3>
                    <p>Expert Islamic instruction for the Holy Quran and religious education.</p>
                </div>
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/sadaqah.png') }}" alt="Sadaqah Jariyah">
                    <h3>Sadaqah Jariyah</h3>
                    <p>Long-term projects like schools, water wells and educational resources.</p>
                </div>
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/latter.png') }}" alt="Zakat">
                    <h3>Zakat</h3>
                    <p>Distribute your Zakat to those truly in need through ISGH's trusted channels.</p>
                </div>
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/mosque2.png') }}" alt="Masjid Maintenance">
                    <h3>Masjid Maintenance</h3>
                    <p>Support daily operations and beautification of our masjid facilities.</p>
                </div>
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/takecare.png') }}" alt="Ongoing Charity">
                    <h3>Ongoing Charity</h3>
                    <p>Sustain charitable programs from Ramadan initiatives to emergency aid.</p>
                </div>
                <div class="mobile-scroll-item">
                    <img src="{{ asset('images/matrimonial_services.png') }}" alt="Matrimonial Services">
                    <h3>Matrimonial Services</h3>
                    <p>Guidance toward a blessed, community-centered marriage journey.</p>
                </div>
            </div>
            <!-- /mobile-side-scroll -->

        </div>
        <!-- /main-container -->


        <!-- ══════════ FOOTER (UNCHANGED) ══════════ -->
        <footer
            style="background:linear-gradient(135deg,#0a5e3a 0%,#0d7a4e 50%,#12a060 100%);border-radius:2rem 2rem 0 0;margin-top:5rem;border-top:10px solid white;border-left:2px solid white;border-right:2px solid white;box-shadow:0 -4px 24px rgba(180,220,255,0.25),inset 0 1px 0 rgba(255,255,255,0.15);">
            <div class="max-w-6xl mx-auto px-8 sm:px-12 py-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-full border-2 border-yellow-500 flex items-center justify-center bg-green-900 flex-shrink-0">
                            <img src="{{ asset('images/logo.png') }}" alt="ISGH Logo">
                        </div>
                    </div>
                    <p class="text-green-200 text-sm leading-relaxed" style="font-family:'SF Pro regular';">
                        Islamic Society of Greater Houston – Building community through faith, education, and service.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Quick Links
                    </h4>
                    <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> About Us</li>
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Prayer Times</li>
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Events</li>
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Programs</li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Services</h4>
                    <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Shifa Clinics</li>
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Quran Academy</li>
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Chaplaincy</li>
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Matrimonial</li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold text-base mb-4" style="font-family:'SF Pro bold';">Contact</h4>
                    <ul class="flex flex-col gap-2.5" style="font-family:'SF Pro regular';">
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Contact Us</li>
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Donate</li>
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Volunteer</li>
                        <li
                            class="flex items-center gap-2 text-green-200 text-sm hover:text-white transition-colors cursor-pointer">
                            <span class="text-green-400">•</span> Newsletter</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-green-700/50 mx-8 sm:mx-12">
                <div class="max-w-6xl mx-auto py-5 flex flex-col sm:flex-row items-center justify-between gap-2"
                    style="font-family:'SF Pro regular';">
                    <p class="text-green-300 text-xs">© 2026 Islamic Society of Greater Houston. All rights reserved.
                    </p>
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
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <nav class="flex flex-col gap-1">
                <a href="{{ route('home') }}"
                    class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Home</a>
                <a href="#" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Centers</a>
                <a href="#" class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Donate</a>
                <a href="{{ route('join') }}"
                    class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Become a Member</a>
                <a href="{{ route('membership-verification') }}"
                    class="text-white/90 hover:bg-white/10 px-4 py-3 rounded-xl text-base">Verify Membership</a>
            </nav>
            <div class="mt-6 flex flex-col gap-3">
                <a href="#"
                    class="bg-white/20 text-white text-center px-6 py-2.5 rounded-full font-semibold">Sign in</a>
                <a href="{{ route('join') }}" style="background:#00d084;"
                    class="text-white text-center px-6 py-2.5 rounded-full font-semibold">Join Now</a>
            </div>
        </div>
    </div>
    <script>
        function openMobileMenu() {
            document.getElementById('mobileMenu').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            document.getElementById('mobileMenu').classList.add('hidden');
            document.body.style.overflow = '';
        }
    </script>

    <!-- ─── PRIMARY MEMBER REVIEW POPUP ────────────────────────────────── -->
    <div id="primaryReviewOverlay" class="confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="prcTitle">
        <div class="confirm-box" style="max-width:420px;">
            <div class="confirm-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <p class="confirm-title" id="prcTitle">Confirm Your Details</p>
            <p class="confirm-body" style="margin-bottom:1rem;">Please verify your primary member information before we check for an existing membership.</p>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.65rem;padding:0.9rem 1rem;text-align:left;font-size:0.82rem;line-height:1.9;color:#374151;">
                <div><span style="font-weight:700;color:#0d7a55;min-width:70px;display:inline-block;">Name</span> <span id="prc_name"></span></div>
                <div><span style="font-weight:700;color:#0d7a55;min-width:70px;display:inline-block;">Email</span> <span id="prc_email"></span></div>
                <div><span style="font-weight:700;color:#0d7a55;min-width:70px;display:inline-block;">Phone</span> <span id="prc_phone"></span></div>
                <div><span style="font-weight:700;color:#0d7a55;min-width:70px;display:inline-block;">D.O.B</span> <span id="prc_dob"></span></div>
                <div><span style="font-weight:700;color:#0d7a55;min-width:70px;display:inline-block;">TX DL</span> <span id="prc_txdl"></span></div>
                <div><span style="font-weight:700;color:#0d7a55;min-width:70px;display:inline-block;">Address</span> <span id="prc_addr"></span></div>
            </div>
            <div class="confirm-actions" style="margin-top:1.25rem;">
                <button class="confirm-btn-no" onclick="closePrimaryReview()">Edit Details</button>
                <button class="confirm-btn-yes" onclick="proceedPrimaryCheck()">Yes, Confirm</button>
            </div>
        </div>
    </div>

    <!-- ─── CONFIRM SUBMISSION POPUP ────────────────────────────────────── -->
    <div id="confirmOverlay" class="confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="confirm-box">
            <div class="confirm-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12l2 2 4-4"/><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <p class="confirm-title" id="confirmTitle">Confirm Submission</p>
            <p class="confirm-body">Please review your information before proceeding. Once submitted, your membership registration will be processed and payment will be charged.<br><br>Are you sure you want to continue?</p>
            <div class="confirm-actions">
                <button class="confirm-btn-no" onclick="closeConfirmPopup()">No, Go Back</button>
                <button class="confirm-btn-yes" onclick="proceedSubmission()">Yes, Submit</button>
            </div>
        </div>
    </div>

    <!-- ── ID SCAN MODAL ──────────────────────────────────────────────────── -->
    <div id="idScanOverlay" class="id-scan-overlay">
        <div class="id-scan-modal">
            <div class="id-scan-header">
                <h3>Scan ID Barcode</h3>
                <button class="id-scan-close-btn" onclick="closeIdScan()">✕</button>
            </div>
            <div class="id-scan-body">
                <p class="id-scan-instruction">
                    <strong>Point the camera at the PDF417 barcode</strong> on the back of the Texas Driver's License or ID card. Hold steady until detected.
                </p>
                <div class="id-scan-viewfinder">
                    <video id="idScanVideo" autoplay playsinline muted></video>
                    <div class="id-scan-corners">
                        <span class="id-scan-corner-br"></span>
                        <span class="id-scan-corner-bl"></span>
                    </div>
                    <div class="id-scan-laser"></div>
                </div>
                <div id="idScanStatus" class="id-scan-status scanning">Scanning for barcode…</div>
                <div class="id-scan-actions">
                    <button class="id-scan-cancel-btn" onclick="closeIdScan()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ── ID SCAN: AAMVA PARSER ────────────────────────────────────────────────
    function parseAamva(raw) {
        const result = { first_name: '', middle_name: '', last_name: '', dob: '', id_number: '' };
        try {
            // AAMVA subfile starts after "@\n\x1e\rANSI " header
            const subfileStart = raw.indexOf('ANSI ');
            const data = subfileStart !== -1 ? raw.slice(subfileStart) : raw;

            const get = (tag) => {
                const idx = data.indexOf(tag);
                if (idx === -1) return '';
                const val = data.slice(idx + tag.length);
                const end = val.search(/[\r\n]/);
                return (end !== -1 ? val.slice(0, end) : val).trim();
            };

            // Try dedicated name fields first (newer AAMVA), fall back to DAA (combined)
            let firstName = get('DAC');
            let lastName  = get('DCS');
            let midName   = get('DAD');

            if (!firstName && !lastName) {
                // Older format: DAA = LAST,FIRST MIDDLE
                const full = get('DAA');
                if (full) {
                    const [last, rest = ''] = full.split(',');
                    const parts = rest.trim().split(/\s+/);
                    lastName  = last.trim();
                    firstName = parts[0] || '';
                    midName   = parts.slice(1).join(' ');
                }
            }

            // DOB: DBB = MMDDYYYY  → MM/DD/YYYY
            const dob = get('DBB');
            let dobFormatted = '';
            if (dob && dob.length >= 8) {
                dobFormatted = `${dob.slice(0,2)}/${dob.slice(2,4)}/${dob.slice(4,8)}`;
            }

            result.first_name  = toTitleCase(firstName);
            result.middle_name = toTitleCase(midName);
            result.last_name   = toTitleCase(lastName);
            result.dob         = dobFormatted;
            result.id_number   = get('DAQ'); // TX DL / ID number
        } catch (e) {
            console.error('AAMVA parse error:', e);
        }
        return result;
    }

    function toTitleCase(str) {
        return (str || '').toLowerCase().replace(/\b\w/g, c => c.toUpperCase()).trim();
    }

    // ── ID SCAN: FILL FORM FIELDS ────────────────────────────────────────────
    function fillIdFields(prefix, data) {
        const set = (suffix, val) => {
            if (!val) return;
            const el = document.getElementById(prefix === 'uni'
                ? `uni_${suffix}`
                : `${prefix}_${suffix}`);
            if (el && !el.disabled) el.value = val;
        };

        if (prefix === 'uni') {
            // Primary member: directly set uni_* fields
            if (data.first_name)  { const el = document.getElementById('uni_first_name');  if (el && !el.disabled) el.value = data.first_name; }
            if (data.middle_name) { const el = document.getElementById('uni_middle_name'); if (el && !el.disabled) el.value = data.middle_name; }
            if (data.last_name)   { const el = document.getElementById('uni_last_name');   if (el && !el.disabled) el.value = data.last_name; }
            if (data.dob)         { const el = document.getElementById('uni_dob');         if (el && !el.disabled) el.value = data.dob; }
            if (data.id_number)   { const el = document.getElementById('uni_txdl');        if (el && !el.disabled) el.value = data.id_number; }
            // Trigger watchers so validation state updates
            ['uni_first_name','uni_last_name','uni_dob','uni_txdl'].forEach(id => {
                document.getElementById(id)?.dispatchEvent(new Event('input', { bubbles: true }));
            });
            updateConfirmBtn();
        } else {
            // Spouse / flat member
            if (data.first_name)  { const el = document.getElementById(`${prefix}_first_name`);  if (el) el.value = data.first_name; }
            if (data.middle_name) { const el = document.getElementById(`${prefix}_middle_name`); if (el) el.value = data.middle_name; }
            if (data.last_name)   { const el = document.getElementById(`${prefix}_last_name`);   if (el) el.value = data.last_name; }
            if (data.dob)         { const el = document.getElementById(`${prefix}_dob`);         if (el) el.value = data.dob; }
            if (data.id_number)   { const el = document.getElementById(`${prefix}_txdl`);        if (el) el.value = data.id_number; }
            [`${prefix}_first_name`,`${prefix}_last_name`,`${prefix}_dob`].forEach(id => {
                document.getElementById(id)?.dispatchEvent(new Event('input', { bubbles: true }));
            });
            checkFormReadiness?.();
        }
    }

    // ── ID SCAN: CAMERA & ZXING ──────────────────────────────────────────────
    let _idScanPrefix    = '';
    let _idScanReader    = null;
    let _idScanStream    = null;
    let _idScanAnimFrame = null;

    function openIdScan(prefix) {
        _idScanPrefix = prefix;
        document.getElementById('idScanOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
        setIdScanStatus('scanning', 'Starting camera…');
        _startIdScanCamera();
    }

    function closeIdScan() {
        _stopIdScanCamera();
        document.getElementById('idScanOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }

    async function _startIdScanCamera() {
        try {
            _idScanStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 960 } }
            });
            const video = document.getElementById('idScanVideo');
            video.srcObject = _idScanStream;
            await video.play();

            if (!window.ZXing) {
                setIdScanStatus('error', 'Barcode library not loaded. Please refresh.');
                return;
            }

            const hints = new Map();
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [ZXing.BarcodeFormat.PDF_417]);
            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
            _idScanReader = new ZXing.BrowserMultiFormatReader(hints);

            setIdScanStatus('scanning', 'Scanning for barcode…');
            _idScanLoop();
        } catch (err) {
            console.error('Camera error:', err);
            const msg = err.name === 'NotAllowedError'
                ? 'Camera access denied. Please allow camera permission and try again.'
                : 'Could not access camera. Please check your device settings.';
            setIdScanStatus('error', msg);
        }
    }

    function _idScanLoop() {
        const video  = document.getElementById('idScanVideo');
        if (!video || !_idScanStream) return;

        if (video.readyState < 2) {
            _idScanAnimFrame = requestAnimationFrame(_idScanLoop);
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        try {
            const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const luminanceSource = new ZXing.RGBLuminanceSource(imgData.data, canvas.width, canvas.height);
            const binaryBitmap    = new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(luminanceSource));
            const result = _idScanReader.decode(binaryBitmap);

            if (result && result.text) {
                _onIdBarcodeDetected(result.text);
                return;
            }
        } catch (e) {
            // NotFoundException is thrown on every frame with no barcode — ignore it
        }

        _idScanAnimFrame = requestAnimationFrame(_idScanLoop);
    }

    function _onIdBarcodeDetected(raw) {
        setIdScanStatus('success', '✓ Barcode detected! Filling in fields…');
        const data = parseAamva(raw);

        const hasData = data.first_name || data.last_name || data.dob;
        if (!hasData) {
            setIdScanStatus('error', 'Could not read ID data. Please try again.');
            _idScanAnimFrame = requestAnimationFrame(_idScanLoop);
            return;
        }

        fillIdFields(_idScanPrefix, data);
        setTimeout(closeIdScan, 1200);
    }

    function _stopIdScanCamera() {
        if (_idScanAnimFrame) { cancelAnimationFrame(_idScanAnimFrame); _idScanAnimFrame = null; }
        if (_idScanStream)    { _idScanStream.getTracks().forEach(t => t.stop()); _idScanStream = null; }
        const video = document.getElementById('idScanVideo');
        if (video) video.srcObject = null;
        _idScanReader = null;
    }

    function setIdScanStatus(type, msg) {
        const el = document.getElementById('idScanStatus');
        if (!el) return;
        el.className = `id-scan-status ${type}`;
        el.textContent = msg;
    }
    </script>
</body>

</html>
