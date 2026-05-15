<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Member Dashboard — ISGH</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:           #f5f6f8;
      --surface:      #ffffff;
      --border:       #eef0f2;
      --text:         #0f172a;
      --text-muted:   #6b7280;
      --text-faint:   #9ca3af;
      --green:        #0d7a55;
      --green-dark:   #064e36;
      --green-mid:    #10b981;
      --green-soft:   #d8f3e4;
      --teal-soft:    #cdebe2;
      --yellow-soft:  #eef6c4;
      --danger:       #ef4444;
      --radius:       18px;
      --radius-sm:    12px;
      --shadow:       0 4px 24px rgba(15, 23, 42, 0.05);
      --shadow-lg:    0 10px 40px rgba(15, 23, 42, 0.08);
    }

    html, body {
      height: 100%;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--bg);
      color: var(--text);
      font-size: 14px;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }

    a { text-decoration: none; color: inherit; }
    button { font-family: inherit; cursor: pointer; }

    /* ── Layout ── */
    .app {
      display: grid;
      grid-template-columns: 248px 1fr;
      min-height: 100vh;
      transition: grid-template-columns .3s ease;
      max-width: 100%;
      overflow-x: hidden;
    }
    .app.sidebar-collapsed { grid-template-columns: 0 1fr; }
    .app.sidebar-collapsed .sidebar {
      transform: translateX(-100%);
      transition: transform .3s ease;
    }

    /* ── Sidebar ── */
    .sidebar {
      background: #fff;
      border-right: 1px solid var(--border);
      padding: 22px 16px;
      position: sticky;
      top: 0;
      height: 100vh;
      display: flex;
      flex-direction: column;
      gap: 18px;
      box-shadow: 1px 0 0 rgba(15,23,42,0.02);
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 4px 8px 18px;
      border-bottom: 1px solid var(--border);
    }
    .brand-left { display: flex; align-items: center; gap: 10px; }
    .brand-logo {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: #1a4a2e;
      border: 1.5px solid #c8a84b;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
    }
    .brand-logo img { width: 26px; height: 26px; object-fit: contain; }
    .brand-name { font-size: 16px; font-weight: 700; color: var(--text); letter-spacing: -0.2px; }
    .sidebar-toggle {
      background: transparent;
      border: none;
      color: var(--text-muted);
      padding: 6px;
      border-radius: 8px;
      display: inline-flex;
    }
    .sidebar-toggle:hover { background: var(--bg); }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 4px;
      flex: 1;
      overflow-y: auto;
    }
    .nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 14px;
      border-radius: 12px;
      color: #475569;
      font-size: 14px;
      font-weight: 500;
      transition: background .15s, color .15s;
    }
    .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; stroke-width: 1.8; }
    .nav-item:hover { background: #f8fafc; color: var(--text); }
    .nav-item.active {
      background: linear-gradient(135deg, #0e7a52 0%, #0a5a3d 100%);
      color: #fff;
      box-shadow: 0 6px 16px rgba(13, 122, 82, 0.25);
    }
    .nav-item.active:hover { color: #fff; }

    /* ── Main ── */
    .main { display: flex; flex-direction: column; min-width: 0; }

    .topbar {
      background: #fff;
      border-bottom: 1px solid var(--border);
      padding: 18px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky; top: 0; z-index: 30;
    }
    .topbar-left { display: flex; align-items: center; gap: 16px; }
    .hamburger {
      background: transparent; border: none; padding: 6px;
      border-radius: 8px; color: var(--text); display: none;
    }
    .hamburger:hover { background: var(--bg); }
    .page-title { font-size: 20px; font-weight: 700; color: var(--text); letter-spacing: -0.3px; }
    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .user-name { font-size: 14px; font-weight: 600; color: var(--text); }

    /* ── Content grid ── */
    .content {
      padding: 26px 32px 40px;
      display: grid;
      grid-template-columns: minmax(0, 1fr) 320px;
      gap: 22px;
      align-items: start;
    }

    /* ── Cards ── */
    .card {
      background: var(--surface);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 22px 24px;
    }
    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
    }
    .card-title { font-size: 18px; font-weight: 700; letter-spacing: -0.2px; color: var(--text); }
    .view-all {
      font-size: 13px; font-weight: 500; color: var(--text-muted);
      display: inline-flex; align-items: center; gap: 4px;
      transition: color .15s;
    }
    .view-all:hover { color: var(--green); }
    .view-all svg { width: 14px; height: 14px; }

    /* ── Membership Status (big card) ── */
    .status-card {
      background: linear-gradient(135deg, #eaf7f0 0%, #f6fbf8 45%, #ffffff 100%);
      border-radius: 22px;
      padding: 26px 28px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }
    .status-head {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 22px; gap: 16px;
    }
    .status-title-row { display: flex; align-items: center; gap: 12px; }
    .status-title-row h2 { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
    .badge-active {
      background: var(--green);
      color: #fff;
      font-size: 12px;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 999px;
      letter-spacing: 0.2px;
    }
    .renewal-block { text-align: right; }
    .renewal-label { font-size: 12px; color: var(--text-muted); margin-bottom: 2px; }
    .renewal-date { font-size: 17px; font-weight: 700; color: var(--text); letter-spacing: -0.2px; }
    .renewal-remaining { font-size: 11px; color: var(--danger); margin-top: 2px; }

    .status-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
    }
    .status-tile {
      background: #fff;
      border-radius: 14px;
      padding: 16px 16px 18px;
      box-shadow: 0 2px 10px rgba(15,23,42,0.04);
      position: relative;
      min-height: 110px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .status-tile .tile-head {
      display: flex; justify-content: space-between; align-items: center;
      color: var(--text-muted); font-size: 12px; font-weight: 500;
    }
    .status-tile .tile-icon {
      width: 26px; height: 26px;
      border-radius: 8px;
      background: #f1f5f4;
      display: flex; align-items: center; justify-content: center;
      color: #64748b;
    }
    .status-tile .tile-icon svg { width: 14px; height: 14px; stroke-width: 1.8; }
    .status-tile .tile-value {
      font-size: 17px; font-weight: 700; letter-spacing: -0.2px; color: var(--text);
      margin-top: 12px;
    }
    .status-tile.featured {
      background: linear-gradient(135deg, #0e7a52 0%, #0a5a3d 100%);
      color: #fff;
    }
    .status-tile.featured .tile-head { color: rgba(255,255,255,0.85); }
    .status-tile.featured .tile-icon { background: rgba(255,255,255,0.18); color: #fff; }
    .status-tile.featured .tile-value { color: #fff; font-size: 18px; }

    /* ── Expired state toggles ── */
    .expired-alert    { display: none; }
    .state-expired    { display: none; }
    .label-expired    { display: none; }

    .status-card.is-expired .expired-alert { display: flex; }
    .status-card.is-expired .state-active  { display: none; }
    .status-card.is-expired .state-expired { display: block; }
    .status-card.is-expired .label-active  { display: none; }
    .status-card.is-expired .label-expired { display: inline; }

    /* ── Expired state ── */
    .status-card.is-expired {
      background: linear-gradient(135deg, #fde6e6 0%, #fcefef 45%, #ffffff 100%);
      border: 1px solid #f5c2c7;
    }
    .status-card.is-expired .badge-active {
      background: #dc2626;
      box-shadow: 0 2px 8px rgba(220,38,38,0.25);
    }
    .status-card.is-expired .renewal-date { color: #b91c1c; }
    .status-card.is-expired .renewal-remaining { color: #b91c1c; }
    .status-card.is-expired .renewal-label {
      display: inline-flex; align-items: center; gap: 6px;
      color: #6b7280;
    }
    .status-card.is-expired .renewal-label svg { width: 14px; height: 14px; stroke-width: 1.8; color: #b91c1c; }

    .status-card.is-expired .status-tile.featured {
      background: linear-gradient(135deg, #c43838 0%, #7a1f1f 100%);
    }
    .status-card.is-expired .status-tile .tile-value { color: #b91c1c; }
    .status-card.is-expired .status-tile.featured .tile-value { color: #fff; }

    .expired-alert {
      align-items: center;
      gap: 12px;
      background: rgba(255, 245, 245, 0.85);
      border: 1px solid #fecaca;
      border-radius: 14px;
      padding: 8px 8px 8px 14px;
      margin-bottom: 18px;
    }
    .expired-alert-icon {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: #fee2e2;
      color: #b91c1c;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .expired-alert-icon svg { width: 14px; height: 14px; stroke-width: 2; }
    .expired-alert-text {
      flex: 1;
      font-size: 13px;
      color: #b91c1c;
      font-weight: 500;
    }
    .btn-renew {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #fff;
      border: 1px solid #fecaca;
      color: #b91c1c;
      font-size: 13px;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 999px;
      transition: background .15s, color .15s, border-color .15s, box-shadow .15s;
      white-space: nowrap;
      box-shadow: 0 1px 2px rgba(185, 28, 28, 0.06);
    }
    .btn-renew:hover {
      background: #b91c1c;
      color: #fff;
      border-color: #b91c1c;
      box-shadow: 0 4px 12px rgba(185, 28, 28, 0.25);
    }
    .btn-renew svg { width: 13px; height: 13px; stroke-width: 2; }

    @media (max-width: 640px) {
      .expired-alert { flex-wrap: wrap; }
      .expired-alert-text { flex: 1 1 100%; order: 2; }
      .btn-renew { order: 3; }
      .expired-alert-icon { order: 1; }
    }

    /* ── Two-column row (Invoices + Payment Overview) ── */
    .row-2 {
      display: grid;
      grid-template-columns: 360px minmax(0, 1fr);
      gap: 22px;
      margin-top: 22px;
    }

    /* Invoices */
    .invoice-list { display: flex; flex-direction: column; gap: 10px; }
    .invoice-item {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 14px;
      background: #f6f8f9;
      border-radius: 12px;
      transition: background .15s;
    }
    .invoice-item:hover { background: #eef2f4; }
    .invoice-icon {
      width: 36px; height: 36px;
      border-radius: 10px;
      background: #fff;
      display: flex; align-items: center; justify-content: center;
      color: #64748b;
      flex-shrink: 0;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .invoice-icon svg { width: 16px; height: 16px; stroke-width: 1.8; }
    .invoice-meta { flex: 1; min-width: 0; }
    .invoice-id { font-size: 13px; font-weight: 600; color: var(--text); }
    .invoice-date { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
    .invoice-right { text-align: right; }
    .invoice-amount { font-size: 13px; font-weight: 700; color: var(--text); }
    .invoice-view { font-size: 11px; color: var(--green); font-weight: 500; margin-top: 1px; }

    /* Payment Overview */
    .payment-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    .pay-tile {
      border-radius: 16px;
      padding: 18px 20px 20px;
      position: relative;
      min-height: 130px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .pay-tile.next { background: #eef6c4; }
    .pay-tile.last { background: #cdebe2; }
    .pay-tile.plain { background: #f4f6f7; }
    .pay-tile-head {
      display: flex; align-items: center; justify-content: space-between;
    }
    .pay-tile-label { font-size: 13px; font-weight: 500; color: #334155; }
    .pay-tile-icon {
      width: 28px; height: 28px;
      border-radius: 10px;
      background: rgba(255,255,255,0.6);
      display: flex; align-items: center; justify-content: center;
      color: #475569;
    }
    .pay-tile-icon svg { width: 14px; height: 14px; stroke-width: 1.8; }
    .pay-tile-amount {
      font-size: 28px; font-weight: 700; color: var(--text);
      letter-spacing: -0.5px;
      margin-top: 6px;
    }
    .pay-tile-date { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
    .pay-tile.plain .pay-tile-amount { font-size: 22px; }
    .pay-tile.plain { min-height: 100px; }

    /* ── Right column ── */
    .right-col { display: flex; flex-direction: column; gap: 22px; }

    /* Quick Profile */
    .profile-card {
      background: linear-gradient(180deg, #ffffff 0%, #ffffff 60%, #e8f5ef 100%);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 22px 22px 26px;
    }
    .profile-head {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 18px;
    }
    .profile-edit {
      width: 30px; height: 30px;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: #fff;
      display: inline-flex; align-items: center; justify-content: center;
      color: var(--text-muted);
      transition: background .15s, color .15s;
    }
    .profile-edit:hover { background: var(--bg); color: var(--text); }
    .profile-edit svg { width: 14px; height: 14px; stroke-width: 1.8; }
    .profile-center { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; }
    .avatar-circle {
      width: 110px; height: 110px;
      border-radius: 50%;
      background: #d9dee3;
      display: flex; align-items: center; justify-content: center;
      color: #94a3b8;
      overflow: hidden;
    }
    .avatar-circle svg { width: 78px; height: 78px; }
    .profile-name { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
    .profile-info { width: 100%; margin-top: 14px; display: flex; flex-direction: column; gap: 12px; }
    .profile-row {
      display: flex; align-items: center; gap: 10px;
      color: var(--text-muted); font-size: 13px;
    }
    .profile-row svg { width: 16px; height: 16px; color: var(--green); flex-shrink: 0; stroke-width: 1.8; }
    .profile-row span { color: var(--text); }

    /* Quick Links */
    .ql-list { display: flex; flex-direction: column; gap: 4px; }
    .ql-item {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 4px;
      color: var(--text);
      font-size: 14px;
      font-weight: 500;
      border-bottom: 1px solid var(--border);
      transition: color .15s;
    }
    .ql-item:last-child { border-bottom: none; }
    .ql-item:hover { color: var(--green); }
    .ql-icon {
      width: 32px; height: 32px;
      border-radius: 10px;
      background: #f1f5f4;
      display: inline-flex; align-items: center; justify-content: center;
      color: #475569;
      flex-shrink: 0;
    }
    .ql-icon svg { width: 16px; height: 16px; stroke-width: 1.8; }
    .ql-arrow { margin-left: auto; color: var(--text-muted); }
    .ql-arrow svg { width: 14px; height: 14px; stroke-width: 1.8; }

    /* ── Sidebar overlay (mobile) ── */
    .sidebar-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 45;
      opacity: 0;
      visibility: hidden;
      transition: opacity .3s ease, visibility .3s ease;
    }
    .sidebar-overlay.open { opacity: 1; visibility: visible; }

    /* ── Bottom Nav (mobile) ── */
    .bottom-nav {
      display: none;
      position: fixed;
      left: 0; right: 0; bottom: 0;
      background: #fff;
      border-top: 1px solid var(--border);
      padding: 10px 8px calc(10px + env(safe-area-inset-bottom));
      z-index: 40;
      box-shadow: 0 -4px 20px rgba(15,23,42,0.06);
      justify-content: space-around;
      align-items: center;
    }
    .bn-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      color: var(--text-muted);
      font-size: 11px;
      font-weight: 500;
      padding: 4px 10px;
      border-radius: 10px;
      flex: 1;
      max-width: 80px;
      transition: color .15s;
    }
    .bn-item svg { width: 20px; height: 20px; stroke-width: 1.8; }
    .bn-item.active { color: var(--green); }
    .bn-item.active .bn-icon {
      background: linear-gradient(135deg, #0e7a52 0%, #0a5a3d 100%);
      color: #fff;
    }
    .bn-icon {
      width: 36px; height: 36px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      transition: background .2s, color .2s;
    }
    .bn-item.active .bn-icon { box-shadow: 0 4px 12px rgba(13,122,82,0.3); }

    /* ── Responsive ── */
    @media (max-width: 1180px) {
      .content { grid-template-columns: minmax(0, 1fr); }
      .right-col { flex-direction: row; flex-wrap: wrap; }
      .right-col > * { flex: 1 1 320px; }
    }
    @media (max-width: 960px) {
      .row-2 { grid-template-columns: 1fr; }
      .status-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
      .app { grid-template-columns: 1fr; }
      .sidebar {
        position: fixed; left: 0; top: 0;
        width: 80%;
        max-width: 320px;
        height: 100vh;
        z-index: 50;
        transform: translateX(-100%);
        transition: transform .3s ease;
        border-top-right-radius: 28px;
        border-bottom-right-radius: 28px;
        border-right: none;
        box-shadow: 8px 0 30px rgba(15,23,42,0.15);
        padding: 18px 14px 20px;
      }
      .sidebar.open { transform: translateX(0); }
      .hamburger { display: inline-flex; }
      .topbar { padding: 14px 18px; }
      .content { padding: 18px 18px 32px; gap: 16px; }
      .row-2 { gap: 16px; margin-top: 16px; }
      .status-card { padding: 20px; }
      .bottom-nav { display: flex; }
      body { padding-bottom: 78px; }
    }
    /* Default: hide mobile-only button on desktop */
    .btn-renew-mobile { display: none; }

    /* ── Mobile-specific responsive polish (≤768px) ── */
    @media (max-width: 768px) {
      /* Force a single-column flow so Quick Profile drops below Membership Status */
      .content { grid-template-columns: 1fr; }
      .right-col { flex-direction: column; }
      .right-col > * { flex: 1 1 auto; width: 100%; }

      /* Quick Profile — full-width on mobile */
      .profile-card { padding: 22px; }
      .profile-row { justify-content: flex-start; }
    }

    @media (max-width: 520px) {
      /* Prevent any child from forcing horizontal scroll */
      html, body, .app, .main { max-width: 100%; overflow-x: hidden; }
      .main, .content, .status-card, .card, .profile-card {
        max-width: 100%;
        min-width: 0;
      }
      .content { padding: 14px 14px 28px; gap: 14px; }
      .row-2 { gap: 14px; margin-top: 14px; min-width: 0; }
      .row-2 > * { min-width: 0; }

      /* Page-level spacing */
      .page-title { font-size: 18px; }
      .card { padding: 16px; }
      .status-card { padding: 14px; }
      .profile-card { padding: 18px; }

      /* Header — title+badge LEFT, renewal block RIGHT, side by side */
      .status-head {
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
        flex-wrap: nowrap;
      }
      .status-title-row {
        flex-direction: row;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        min-width: 0;
        flex: 1 1 auto;
      }
      .status-title-row h2 {
        font-size: 15px;
        line-height: 1.2;
        white-space: nowrap;
      }
      .badge-active { font-size: 10.5px; padding: 3px 10px; }

      .renewal-block {
        text-align: right;
        flex-shrink: 0;
        min-width: 0;
        max-width: 50%;
      }
      .renewal-label {
        font-size: 10.5px;
        white-space: nowrap;
        justify-content: flex-end;
      }
      .renewal-label svg { width: 11px; height: 11px; }
      .renewal-date {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
      }
      .renewal-remaining { font-size: 10px; margin-top: 2px; }

      /* Mobile standalone Renew button above the alert */
      .status-card.is-expired .btn-renew-mobile {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: auto;
        max-width: 100%;
        align-self: flex-start;
        margin-bottom: 10px;
        padding: 7px 14px;
        font-size: 12px;
      }
      .status-card.is-expired .btn-renew-desktop { display: none; }

      /* Compact alert without internal button */
      .status-card.is-expired .expired-alert {
        padding: 8px 10px;
        gap: 8px;
        margin-bottom: 12px;
        flex-wrap: nowrap;
        max-width: 100%;
      }
      .status-card.is-expired .expired-alert-text {
        font-size: 11px;
        line-height: 1.4;
        min-width: 0;
      }
      .status-card.is-expired .expired-alert-icon {
        width: 22px;
        height: 22px;
        flex-shrink: 0;
      }
      .status-card.is-expired .expired-alert-icon svg { width: 11px; height: 11px; }

      /* Tile grid — 2 columns */
      .status-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
      }
      .status-tile {
        min-height: auto;
        padding: 12px 12px 14px;
        border-radius: 12px;
        min-width: 0;
        overflow: hidden;
      }
      .status-tile .tile-head { font-size: 10.5px; gap: 6px; min-width: 0; }
      .status-tile .tile-head > span:first-child {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
      }
      .status-tile .tile-icon { width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0; }
      .status-tile .tile-icon svg { width: 11px; height: 11px; }
      .status-tile .tile-value {
        font-size: 13px;
        margin-top: 8px;
        line-height: 1.25;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .status-tile.featured .tile-value { font-size: 14px; }

      /* Payment overview — single column */
      .payment-grid { grid-template-columns: 1fr; gap: 10px; }
      .pay-tile { min-height: 110px; padding: 14px 16px; }
      .pay-tile-amount { font-size: 24px; }

      /* Quick profile — keep clean centered layout on mobile */
      .profile-info { padding: 0 4px; }
      .profile-row { font-size: 12.5px; gap: 10px; min-width: 0; }
      .profile-row span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }

    @media (max-width: 380px) {
      .topbar { padding: 12px 14px; }
      .content { padding: 14px 14px 28px; }
      .status-card { padding: 16px; }
      .status-title-row h2 { font-size: 17px; }
      .bn-item { font-size: 10px; }
      .bn-icon { width: 32px; height: 32px; }
    }
    /* ── Page toggle (dashboard ↔ profile, no reload) ── */
    [data-page] { display: grid; }
    [data-page].hidden { display: none !important; }

    /* ── Profile-section styles (scoped within #profilePage so they don't override dashboard) ── */
    #profilePage .left-col,
    #profilePage .right-col { display: flex; flex-direction: column; gap: 22px; min-width: 0; }

    #profilePage .info-card {
      background: linear-gradient(135deg, #eaf7f0 0%, #f6fbf8 45%, #ffffff 100%);
      border-radius: 22px;
      padding: 24px 26px;
      box-shadow: var(--shadow);
    }
    #profilePage .info-head {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 20px; gap: 16px;
    }
    #profilePage .info-title-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    #profilePage .info-title-row h2 { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
    #profilePage .renewal-date { font-size: 17px; font-weight: 700; letter-spacing: -0.2px; color: var(--green); }
    #profilePage .renewal-remaining { font-size: 11px; color: #ef4444; margin-top: 2px; }

    #profilePage .info-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
    }
    #profilePage .info-tile {
      background: #fff;
      border-radius: 14px;
      padding: 16px 16px 18px;
      box-shadow: 0 2px 10px rgba(15,23,42,0.04);
      min-height: 120px;
      display: flex; flex-direction: column; justify-content: space-between;
    }
    #profilePage .info-tile .tile-head {
      display: flex; justify-content: space-between; align-items: center;
      color: var(--text-muted); font-size: 12px; font-weight: 500;
    }
    #profilePage .info-tile .tile-icon {
      width: 30px; height: 30px; border-radius: 50%;
      background: #fff; color: #64748b;
      border: 1px solid #e6ebe8;
      display: flex; align-items: center; justify-content: center;
    }
    #profilePage .info-tile .tile-icon svg { width: 14px; height: 14px; stroke-width: 1.8; }
    #profilePage .info-tile .tile-value {
      font-size: 18px; font-weight: 700; letter-spacing: -0.3px;
      margin-top: 10px;
    }
    #profilePage .info-tile.featured {
      background: linear-gradient(135deg, #0e7a52 0%, #0a5a3d 100%);
      color: #fff;
      box-shadow: 0 8px 22px rgba(13,122,82,0.25);
    }
    #profilePage .info-tile.featured .tile-head { color: rgba(255,255,255,0.9); font-size: 12.5px; }
    #profilePage .info-tile.featured .tile-icon {
      background: rgba(255,255,255,0.2);
      color: #fff;
      border-color: rgba(255,255,255,0.25);
    }
    #profilePage .info-tile.featured .tile-value { color: #fff; font-size: 18px; }
    #profilePage .btn-change {
      margin-top: 12px;
      background: #fff; color: #1f2937;
      border: none;
      font-size: 12px; font-weight: 600;
      padding: 8px 14px; border-radius: 999px;
      align-self: flex-start;
      display: inline-flex; align-items: center; gap: 6px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      transition: background .15s, transform .15s, box-shadow .15s;
    }
    #profilePage .btn-change svg { width: 12px; height: 12px; stroke-width: 2; color: #4b5563; }
    #profilePage .btn-change:hover {
      background: #f3f4f6; transform: translateY(-1px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    }

    #profilePage .form-actions { display: flex; gap: 18px; flex-shrink: 0; align-items: center; }
    #profilePage .btn-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 14px; font-weight: 600;
      color: var(--green);
      background: transparent; border: none;
      padding: 4px 2px;
      transition: color .15s, opacity .15s;
    }
    #profilePage .btn-link:hover { color: var(--green-dark); }
    #profilePage .btn-link svg { width: 14px; height: 14px; stroke-width: 2; }

    #profilePage .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px 20px;
    }
    #profilePage .field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    #profilePage .field label { font-size: 12px; font-weight: 500; color: #475569; }
    #profilePage .field label .req { color: var(--green); margin-left: 2px; }
    #profilePage .field input, #profilePage .field select {
      width: 100%;
      background: #f4f6f8;
      border: 1px solid transparent;
      border-radius: 10px;
      padding: 11px 14px;
      font-size: 14px;
      color: var(--text);
      font-family: inherit;
      outline: none;
      transition: border-color .15s, background .15s, box-shadow .15s;
    }
    #profilePage .field input::placeholder { color: #9ca3af; }
    #profilePage .field input:focus, #profilePage .field select:focus {
      background: #fff;
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(13,122,82,0.12);
    }

    #profilePage .same-as-primary {
      display: inline-block;
      margin-top: 16px;
      font-size: 13px; font-weight: 500;
      color: var(--green);
      text-decoration: underline;
      cursor: pointer;
      transition: color .15s;
    }
    #profilePage .same-as-primary:hover { color: var(--green-dark); }

    #profilePage .save-banner {
      display: none;
      align-items: center; gap: 8px;
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      color: #065f46;
      padding: 10px 14px;
      border-radius: 10px;
      font-size: 13px; font-weight: 500;
      margin-bottom: 14px;
    }
    #profilePage .save-banner.visible { display: flex; }
    #profilePage .save-banner svg { width: 16px; height: 16px; }

    #profilePage .profile-card-title {
      text-align: left;
      font-size: 16px; font-weight: 700;
      color: var(--text);
      letter-spacing: -0.2px;
      margin-bottom: 16px;
    }
    #profilePage .mobile-edit-link { display: none; }
    #profilePage .spouse-profile {
      background: linear-gradient(180deg, #ffffff 0%, #ffffff 55%, #fdf2dc 100%);
    }
    #profilePage .card-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
    #profilePage .card-title { font-size: 18px; font-weight: 700; letter-spacing: -0.2px; color: var(--text); }

    @media (max-width: 960px) {
      #profilePage .info-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
      #profilePage .info-card { padding: 20px; }
      #profilePage .form-actions { width: 100%; }
    }
    @media (max-width: 768px) {
      /* On mobile, flatten left-col + right-col into a single flex flow so
         we can reorder profile cards to sit between forms per the design */
      #profilePage { display: flex; flex-direction: column; gap: 16px; }
      #profilePage .left-col, #profilePage .right-col {
        display: contents;
      }

      /* Mobile stacking order:
         1. Membership Information (info-card)         → 1
         2. Primary Profile card                       → 2
         3. Primary Member Information (form #1)       → 3
         4. Spouse Profile card                        → 4
         5. Spouse Information (form #2)               → 5
         6. Quick Links                                → 6 */
      #profilePage .info-card                                { order: 1; }
      #profilePage .right-col > .profile-card:nth-of-type(1) { order: 2; }
      #profilePage .left-col  > .card:nth-of-type(1)         { order: 3; }
      #profilePage .right-col > .profile-card:nth-of-type(2) { order: 4; }
      #profilePage .left-col  > .card:nth-of-type(2)         { order: 5; }
      #profilePage .right-col > .card                        { order: 6; }
    }

    @media (max-width: 520px) {
      #profilePage .info-card, #profilePage .card, #profilePage .profile-card { max-width: 100%; min-width: 0; }
      #profilePage .info-card { padding: 16px; }

      /* Header: title+badge LEFT, Next Renewal RIGHT — keep side by side */
      #profilePage .info-head {
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 14px;
        flex-wrap: nowrap;
      }
      #profilePage .info-title-row {
        flex-direction: row; align-items: center;
        gap: 8px; flex-wrap: wrap;
        min-width: 0; flex: 1 1 auto;
      }
      #profilePage .info-title-row h2 { font-size: 15px; line-height: 1.2; }
      #profilePage .badge-active { font-size: 10.5px; padding: 3px 10px; }
      #profilePage .renewal-block { text-align: right; flex-shrink: 0; min-width: 0; }
      #profilePage .renewal-label { font-size: 10.5px; white-space: nowrap; }
      #profilePage .renewal-date { font-size: 13px; white-space: nowrap; }
      #profilePage .renewal-remaining { font-size: 10px; }

      /* 2-column tile grid */
      #profilePage .info-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
      #profilePage .info-tile {
        padding: 12px 14px 14px;
        min-height: auto;
        border-radius: 12px;
        overflow: hidden;
      }
      #profilePage .info-tile .tile-head { font-size: 10.5px; }
      #profilePage .info-tile .tile-icon { width: 24px; height: 24px; }
      #profilePage .info-tile .tile-icon svg { width: 11px; height: 11px; }
      #profilePage .info-tile .tile-value {
        font-size: 14px; margin-top: 8px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
      }
      #profilePage .info-tile.featured .tile-value { font-size: 14px; }
      /* Hide the Change Membership Level button on mobile */
      #profilePage .info-tile.featured .btn-change { display: none; }

      /* Profile cards — big avatar, large name, amber Edit link */
      #profilePage .profile-card { padding: 22px 22px 26px; text-align: center; }
      #profilePage .profile-card-title { font-size: 16px; margin-bottom: 4px; text-align: left; }
      #profilePage .profile-card .avatar-circle { width: 150px; height: 150px; margin: 12px auto 16px; }
      #profilePage .profile-card .avatar-circle svg { width: 105px; height: 105px; }
      #profilePage .profile-card .profile-name { font-size: 26px; font-weight: 700; }
      #profilePage .profile-card .profile-sub  { font-size: 14px; margin-top: 4px; }
      #profilePage .profile-card .mobile-edit-link {
        display: inline-flex;
        align-items: center; gap: 6px;
        margin-top: 16px;
        color: #d97706;
        font-size: 15px; font-weight: 600;
        background: transparent; border: none;
      }
      #profilePage .profile-card .mobile-edit-link svg { width: 16px; height: 16px; stroke-width: 2; }

      /* Form cards — single column, hide "Edit Profile" link (amber link drives edit) */
      #profilePage .card { padding: 18px; }
      #profilePage .card .card-header {
        flex-wrap: wrap; gap: 10px;
        justify-content: space-between; align-items: flex-start;
      }
      #profilePage .card .card-title { font-size: 17px; line-height: 1.3; }
      #profilePage .form-actions { width: auto; gap: 14px; }
      #profilePage .card .card-header .btn-link:first-of-type { display: none; }
      #profilePage .form-grid { grid-template-columns: 1fr; gap: 14px; }
      #profilePage .form-actions .btn-link { font-size: 14px; padding: 0; }

      /* Quick Links full-width at bottom */
      #profilePage .ql-list { gap: 2px; }
      #profilePage .ql-item { padding: 12px 4px; font-size: 14px; }
    }
    @media (max-width: 380px) {
      #profilePage .info-card { padding: 14px; }
      #profilePage .info-title-row h2 { font-size: 14px; }
      #profilePage .profile-card .avatar-circle { width: 130px; height: 130px; }
      #profilePage .profile-card .avatar-circle svg { width: 92px; height: 92px; }
      #profilePage .profile-card .profile-name { font-size: 22px; }
    }

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
      background: #f6f8f9;
      border-radius: var(--radius-sm);
      padding: 12px 14px;
      margin: 16px 0 14px;
    }
    .renew-pay-row .label { font-size: 13px; font-weight: 500; color: #334155; }
    .renew-pay-row .amount { font-size: 16px; font-weight: 700; color: var(--text); }
    .renew-field { margin-bottom: 14px; }
    .renew-field label {
      display: block; font-size: 12px; font-weight: 500;
      color: #475569; margin-bottom: 6px;
    }
    .renew-field input[type="number"] {
      width: 100%;
      background: #f4f6f8;
      border: 1px solid transparent;
      border-radius: 10px;
      padding: 11px 14px;
      font-size: 14px; color: var(--text);
      font-family: inherit; outline: none;
      transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .renew-field input[type="number"]:focus {
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
</head>
<body>

@php
  /** @var \App\Support\MemberProfile $profile */
  $isExpired   = $profile->isExpired();
  $daysLeft    = $profile->daysLeft();
  $daysOverdue = $profile->daysOverdue();
  $isLifetime  = stripos($profile->level, 'lifetime') !== false;
@endphp

<div class="app">

  {{-- ── Sidebar ── --}}
  @include('member-portal.partials.sidebar', ['active' => 'dashboard', 'mode' => 'spa'])

  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  {{-- ── Main ── --}}
  <div class="main">

    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()" aria-label="Open menu">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
        <h1 class="page-title">Dashboard</h1>
      </div>
      <div class="topbar-right">
        <span class="user-name">{{ $profile->fullName ?: 'Member' }}</span>
      </div>
    </header>

    <section class="content" id="dashboardPage" data-page="dashboard">

      {{-- ── LEFT main column ── --}}
      <div>

        {{-- Membership Status --}}
        <div class="status-card {{ $isExpired ? 'is-expired' : '' }}" id="statusCard">
          <div class="status-head">
            <div class="status-title-row">
              <h2>Membership Status</h2>
              <span class="badge-active" data-status-text>{{ $isExpired ? 'Expired' : $profile->status }}</span>
            </div>
            <div class="renewal-block">
              {{-- Active state --}}
              <div class="state-active">
                <div class="renewal-label">Next Renewal</div>
                <div class="renewal-date">{{ $profile->renewalFormatted() ?: '—' }}</div>
                @if($daysLeft !== null)
                  <div class="renewal-remaining">{{ $daysLeft }} days remaining</div>
                @endif
              </div>
              {{-- Expired state --}}
              <div class="state-expired">
                <div class="renewal-label">
                  <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                  </svg>
                  Payment Due
                </div>
                <div class="renewal-date">{{ $profile->renewalFormatted() ?: '—' }}</div>
                @if($daysOverdue !== null)
                  <div class="renewal-remaining">{{ $daysOverdue }} days overdue</div>
                @endif
              </div>
            </div>
          </div>

          @unless($isLifetime)
          {{-- Mobile-only standalone Renew button (above alert) --}}
          <a href="#" class="btn-renew btn-renew-mobile">
            Renew Membership
            <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
          </a>
          @endunless

          {{-- Expired banner (toggled via CSS) --}}
          <div class="expired-alert">
            <span class="expired-alert-icon">
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <span class="expired-alert-text">Your membership has expired. Renew now to restore access.</span>
            @unless($isLifetime)
            <a href="#" class="btn-renew btn-renew-desktop">
              Renew Membership
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </a>
            @endunless
          </div>

          <div class="status-grid">
            <div class="status-tile featured">
              <div class="tile-head">
                <span>Membership Type</span>
                <span class="tile-icon">
                  <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <rect x="2" y="6" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                  </svg>
                </span>
              </div>
              <div class="tile-value">{{ $profile->level ?: '—' }}</div>
            </div>

            <div class="status-tile">
              <div class="tile-head">
                <span>Member Since</span>
                <span class="tile-icon">
                  <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                  </svg>
                </span>
              </div>
              <div class="tile-value">{{ $profile->memberSinceFormatted() ?: '—' }}</div>
            </div>

            <div class="status-tile">
              <div class="tile-head">
                <span>
                  <span class="label-active">Yearly Contribution</span>
                  <span class="label-expired">Yearly Fee</span>
                </span>
                <span class="tile-icon">
                  <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                  </svg>
                </span>
              </div>
              <div class="tile-value">{{ $profile->yearlyFee ?: '—' }}</div>
            </div>

            <div class="status-tile">
              <div class="tile-head">
                <span>TXDL#/ TXID#</span>
                <span class="tile-icon">
                  <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="7" y1="15" x2="11" y2="15"/>
                  </svg>
                </span>
              </div>
              <div class="tile-value">{{ $profile->txId ?: '—' }}</div>
            </div>
          </div>
        </div>

        {{-- Invoices + Payment Overview --}}
        <div class="row-2">

          {{-- Recent Invoices --}}
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Recent Invoices</h3>
              <a href="#" class="view-all">View all
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
            </div>
            <div class="invoice-list">
              @forelse($profile->invoices as $inv)
                <div class="invoice-item">
                  <div class="invoice-icon">
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                  </div>
                  <div class="invoice-meta">
                    <div class="invoice-id">{{ $inv['number'] }}</div>
                    <div class="invoice-date">{{ $inv['dateLabel'] ?: '—' }}</div>
                  </div>
                  <div class="invoice-right">
                    <div class="invoice-amount">${{ number_format($inv['amount'], 2) }}</div>
                    <a href="{{ $inv['url'] }}" class="invoice-view">View</a>
                  </div>
                </div>
              @empty
                <div class="invoice-item" style="justify-content:center;color:var(--text-muted);">
                  No invoices yet
                </div>
              @endforelse
            </div>
          </div>

          {{-- Payment Overview --}}
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Payment Overview</h3>
              <a href="#" class="view-all">View all
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
            </div>
            @php $next = $profile->nextPayment(); $last = $profile->lastPayment(); @endphp
            <div class="payment-grid">
              <div class="pay-tile next">
                <div class="pay-tile-head">
                  <span class="pay-tile-label">Next Payment</span>
                  <span class="pay-tile-icon">
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                      <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                  </span>
                </div>
                <div>
                  <div class="pay-tile-amount">{{ $next ? '$' . number_format($next['amount'], 2) : '$0.00' }}</div>
                  <div class="pay-tile-date">{{ $next['dateLabel'] ?? '—' }}</div>
                </div>
              </div>

              <div class="pay-tile last">
                <div class="pay-tile-head">
                  <span class="pay-tile-label">Last Payment</span>
                  <span class="pay-tile-icon">
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                      <path d="M21 8v13H3V8"/><rect x="1" y="3" width="22" height="5" rx="1"/><line x1="10" y1="12" x2="14" y2="12"/>
                    </svg>
                  </span>
                </div>
                <div>
                  <div class="pay-tile-amount">{{ $last ? '$' . number_format($last['amount'], 2) : '$0.00' }}</div>
                  <div class="pay-tile-date">{{ $last['dateLabel'] ?? '—' }}</div>
                </div>
              </div>

              <div class="pay-tile plain">
                <div class="pay-tile-head">
                  <span class="pay-tile-label">This Year</span>
                  <span class="pay-tile-icon">
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                      <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><polyline points="9 14 11 16 15 12"/>
                    </svg>
                  </span>
                </div>
                <div class="pay-tile-amount">${{ number_format($profile->paidThisYear(), 2) }}</div>
              </div>

              <div class="pay-tile plain">
                <div class="pay-tile-head">
                  <span class="pay-tile-label">All Time</span>
                  <span class="pay-tile-icon">
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                      <rect x="3" y="4" width="18" height="18" rx="2"/><polyline points="8 14 11 17 16 11"/>
                    </svg>
                  </span>
                </div>
                <div class="pay-tile-amount">${{ number_format($profile->paidAllTime(), 2) }}</div>
              </div>
            </div>
          </div>

        </div>

      </div>

      {{-- ── RIGHT column ── --}}
      <aside class="right-col">

        {{-- Quick Profile --}}
        <div class="profile-card">
          <div class="profile-head">
            <h3 class="card-title">Quick Profile</h3>
            <button class="profile-edit" aria-label="Edit profile">
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/>
              </svg>
            </button>
          </div>

          <div class="profile-center">
            <div class="avatar-circle">
              <svg fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>
              </svg>
            </div>
            <div class="profile-name">{{ $profile->fullName ?: 'Member' }}</div>
          </div>

          <div class="profile-info">
            <div class="profile-row">
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
              </svg>
              <span>{{ $profile->email ?: '—' }}</span>
            </div>
            <div class="profile-row">
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
              <span>{{ $profile->phone ?: '—' }}</span>
            </div>
            <div class="profile-row">
              <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
              </svg>
              <span>{{ trim($profile->city . ', ' . $profile->state, ', ') ?: '—' }}</span>
            </div>
          </div>
        </div>

        {{-- Quick Links --}}
        <div class="card">
          <div class="card-header" style="margin-bottom:8px;">
            <h3 class="card-title">Quick Links</h3>
          </div>
          <div class="ql-list">
            @unless($isLifetime)
            <a href="#" class="ql-item ql-renew-link">
              <span class="ql-icon">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
              </span>
              Renew Membership
              <span class="ql-arrow">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
              </span>
            </a>
            @endunless
            <a href="#" class="ql-item">
              <span class="ql-icon">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                </svg>
              </span>
              View Invoices
              <span class="ql-arrow">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
              </span>
            </a>
            <a href="#" class="ql-item">
              <span class="ql-icon">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
              </span>
              Payment History
              <span class="ql-arrow">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
              </span>
            </a>
            <a href="#" class="ql-item">
              <span class="ql-icon">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
              </span>
              Change Level
              <span class="ql-arrow">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
              </span>
            </a>
            <a href="#" class="ql-item">
              <span class="ql-icon">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
              </span>
              Update Profile
              <span class="ql-arrow">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
              </span>
            </a>
          </div>
        </div>

      </aside>

    </section>

    {{-- ── Profile page (hidden by default, shown via sidebar Profile click) ── --}}
    <section class="content hidden" id="profilePage" data-page="profile">
      @include('member-portal.partials.profile-content')
    </section>

  </div>
</div>

{{-- ── Bottom Nav (mobile only) ── --}}
<nav class="bottom-nav" aria-label="Bottom navigation">
  <a href="#" class="bn-item active" data-page-link="dashboard" onclick="event.preventDefault(); showPage('dashboard')">
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
    </span>
    Dashboard
  </a>
  <a href="#" class="bn-item" data-page-link="profile" onclick="event.preventDefault(); showPage('profile')">
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
    </span>
    Profile
  </a>
  <a href="{{ route('member-portal.payments') }}" class="bn-item">
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/>
      </svg>
    </span>
    Payments
  </a>
  <a href="#" class="bn-item">
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
      </svg>
    </span>
    Records
  </a>
  <a href="#" class="bn-item">
    <span class="bn-icon">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 14l2 2 4-4"/>
      </svg>
    </span>
    Reports
  </a>
</nav>

<script>
  (function () {
    const app     = document.querySelector('.app');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    const isMobile = () => window.innerWidth <= 768;

    function openMobileDrawer()  { sidebar.classList.add('open');    overlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
    function closeMobileDrawer() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow = ''; }

    function toggleDesktopCollapse() { app.classList.toggle('sidebar-collapsed'); }

    window.toggleSidebar = function () {
      if (isMobile()) {
        sidebar.classList.contains('open') ? closeMobileDrawer() : openMobileDrawer();
      } else {
        toggleDesktopCollapse();
      }
    };

    overlay.addEventListener('click', closeMobileDrawer);

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        if (sidebar.classList.contains('open')) closeMobileDrawer();
        else if (app.classList.contains('sidebar-collapsed')) app.classList.remove('sidebar-collapsed');
      }
    });

    window.addEventListener('resize', () => {
      if (!isMobile() && sidebar.classList.contains('open')) closeMobileDrawer();
      if (isMobile() && app.classList.contains('sidebar-collapsed')) app.classList.remove('sidebar-collapsed');
    });

    sidebar.querySelectorAll('.nav-item').forEach(link => {
      link.addEventListener('click', () => {
        if (isMobile()) closeMobileDrawer();
      });
    });

    // ── Membership status toggle ────────────────────────────────────────────
    // Default comes from the server; URL ?status=… overrides; console can call setMembershipStatus().
    let membershipStatus = @json($isExpired ? 'expired' : 'active');
    const serverStatusLabel = @json($profile->status);

    window.setMembershipStatus = function (next) {
      const card = document.getElementById('statusCard');
      if (!card) return;
      membershipStatus = next === 'expired' ? 'expired' : 'active';
      card.classList.toggle('is-expired', membershipStatus === 'expired');

      const badge = card.querySelector('[data-status-text]');
      if (badge) badge.textContent = membershipStatus === 'expired' ? 'Expired' : (serverStatusLabel || 'Active');
    };

    // URL override (?status=expired | ?status=active)
    const urlStatus = new URLSearchParams(location.search).get('status');
    if (urlStatus === 'expired' || urlStatus === 'active') {
      membershipStatus = urlStatus;
    }

    // CRITICAL: apply the state immediately on load so the UI matches `membershipStatus`.
    setMembershipStatus(membershipStatus);

    // ── Page switcher (Dashboard ↔ Profile, no reload) ──────────────────
    window.showPage = function (page) {
      const dashboard = document.getElementById('dashboardPage');
      const profile   = document.getElementById('profilePage');
      if (!dashboard || !profile) return;

      const isProfile = page === 'profile';
      dashboard.classList.toggle('hidden', isProfile);
      profile.classList.toggle('hidden', !isProfile);

      // Update topbar title
      const title = document.querySelector('.page-title');
      if (title) title.textContent = isProfile ? 'Profile' : 'Dashboard';

      // Sync sidebar + bottom-nav active state
      document.querySelectorAll('[data-page-link]').forEach(el => {
        el.classList.toggle('active', el.dataset.pageLink === page);
      });

      // Close mobile drawer if open
      if (isMobile() && sidebar.classList.contains('open')) closeMobileDrawer();

      // Reflect in hash so refresh keeps the page
      try { history.replaceState(null, '', '#' + page); } catch (_) {}
      window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
    };

    // Restore from hash on load (e.g. /dashboard#profile)
    if (location.hash === '#profile') showPage('profile');

    // ── Profile form helpers (Edit / Save / Same-as-primary) ────────────
    function setupForm({ formId, editBtnId, saveBtnId, bannerId }) {
      const form    = document.getElementById(formId);
      const editBtn = document.getElementById(editBtnId);
      const saveBtn = document.getElementById(saveBtnId);
      const banner  = document.getElementById(bannerId);
      if (!form || !editBtn || !saveBtn) return;

      let editing = false;
      const inputs = () => form.querySelectorAll('input, select');

      editBtn.addEventListener('click', () => {
        editing = !editing;
        inputs().forEach(el => { el.readOnly = !editing; });
        editBtn.querySelector('span').textContent = editing ? 'Cancel' : 'Edit Profile';
      });

      saveBtn.addEventListener('click', async () => {
        if (saveBtn.disabled) return;
        // Light email validation
        let valid = true;
        inputs().forEach(el => {
          if (el.type === 'email' && el.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value)) {
            el.style.borderColor = '#ef4444'; el.style.background = '#fef2f2'; valid = false;
          } else {
            el.style.borderColor = ''; el.style.background = '';
          }
        });
        if (!valid) return;

        // Only the primary form persists to WildApricot.
        if (formId === 'formPrimary') {
          const payload = {
            first_name: document.getElementById('p-first')?.value || '',
            last_name:  document.getElementById('p-last')?.value || '',
            email:      document.getElementById('p-email')?.value || '',
            phone:      document.getElementById('p-phone')?.value || '',
            street:     document.getElementById('p-street')?.value || '',
            city:       document.getElementById('p-city')?.value || '',
            state:      document.getElementById('p-state')?.value || '',
            zip:        document.getElementById('p-zip')?.value || '',
            dob:        document.getElementById('p-dob')?.value || '',
            tx_dl:      document.getElementById('p-tx')?.value || '',
          };
          saveBtn.disabled = true;
          try {
            const res = await fetch('{{ route('member-portal.profile.update') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              },
              body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!data.success) {
              editBtn.querySelector('span').textContent = 'Edit Profile';
              alert(data.message || 'Could not save changes.');
              return;
            }
          } catch {
            alert('Network error. Please try again.');
            return;
          } finally {
            saveBtn.disabled = false;
          }
        }

        // Success (or non-primary form): lock inputs, reset button, show banner.
        editing = false;
        inputs().forEach(el => { el.readOnly = true; });
        editBtn.querySelector('span').textContent = 'Edit Profile';
        if (banner) {
          banner.classList.add('visible');
          setTimeout(() => banner.classList.remove('visible'), 2500);
        }
      });
    }
    setupForm({ formId: 'formPrimary', editBtnId: 'btnEditPrimary', saveBtnId: 'btnSavePrimary', bannerId: 'savePrimaryBanner' });
    setupForm({ formId: 'formSpouse',  editBtnId: 'btnEditSpouse',  saveBtnId: 'btnSaveSpouse',  bannerId: 'saveSpouseBanner' });

    const sameLink = document.getElementById('sameAsPrimary');
    if (sameLink) {
      sameLink.addEventListener('click', (e) => {
        e.preventDefault();
        const map = { 'p-street':'s-address', 'p-city':'s-city', 'p-state':'s-state', 'p-zip':'s-zip' };
        Object.entries(map).forEach(([from, to]) => {
          const src = document.getElementById(from);
          const dst = document.getElementById(to);
          if (src && dst) dst.value = src.value;
        });
      });
    }
    const sFirst = document.getElementById('s-first');
    const sLast  = document.getElementById('s-last');
    const sLabel = document.getElementById('spouseNameLabel');
    function syncSpouseName() {
      if (!sLabel) return;
      const name = ((sFirst?.value || sFirst?.placeholder || '') + ' ' + (sLast?.value || sLast?.placeholder || '')).trim();
      if (name) sLabel.textContent = name;
    }
    sFirst?.addEventListener('input', syncSpouseName);
    sLast?.addEventListener('input', syncSpouseName);
    syncSpouseName();
  })();
</script>

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
        <span class="label">Pending Payment</span>
        <span class="amount" id="renewAmountLabel">—</span>
      </div>

      <div class="renew-field" id="renewMonthlyWrap" style="display:none;">
        <label for="renewMonthlyInput">Monthly contribution amount</label>
        <input id="renewMonthlyInput" type="number" min="1" step="0.01" placeholder="Monthly amount" />
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

<script src="https://js.stripe.com/v3/"></script>
<script>
  // ── Renewal modal module (self-contained IIFE) ──────────────────────────
  (function () {
    const modal = document.getElementById('renewModal');
    if (!modal) return; // lifetime members have no modal

    const renewCsrf      = document.querySelector('meta[name="csrf-token"]').content;
    const renewStripeKey = '{{ config("services.stripe.key") }}';

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
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
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

        // Stripe createPaymentMethod — mirrors signup page
        const { paymentMethod, error: pmError } = await _stripe.createPaymentMethod({
          type: 'card',
          card: _cardElement,
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

</body>
</html>
