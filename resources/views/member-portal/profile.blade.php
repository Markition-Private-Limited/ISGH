<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Profile — ISGH Member Portal</title>
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
      /* The grid track is 0-wide when collapsed; give the sidebar its own
         width so translateX(-100%) fully clears it, and clip any overflow
         so its contents don't spill over the page content. */
      width: 248px;
      overflow: hidden;
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
      display: flex; align-items: center; justify-content: space-between;
      padding: 4px 8px 18px;
      border-bottom: 1px solid var(--border);
    }
    .brand-left { display: flex; align-items: center; gap: 10px; }
    .brand-logo {
      width: 36px; height: 36px; border-radius: 50%;
      background: #1a4a2e; border: 1.5px solid #c8a84b;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
    }
    .brand-logo img { width: 26px; height: 26px; object-fit: contain; }
    .brand-name { font-size: 16px; font-weight: 700; color: var(--text); letter-spacing: -0.2px; }
    .sidebar-toggle {
      background: transparent; border: none; color: var(--text-muted);
      padding: 6px; border-radius: 8px; display: inline-flex;
    }
    .sidebar-toggle:hover { background: var(--bg); }

    .sidebar-nav {
      display: flex; flex-direction: column; gap: 4px;
      flex: 1; overflow-y: auto;
    }
    .nav-item {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 14px; border-radius: 12px;
      color: #475569; font-size: 14px; font-weight: 500;
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
    .nav-logout-form { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .nav-item.nav-logout {
      width: 100%; border: none; background: none; cursor: pointer;
      font-family: inherit; font-size: 14px; text-align: left;
    }
    .nav-item.nav-logout:hover { background: #fef2f2; color: #dc2626; }

    /* ── Main ── */
    .main { display: flex; flex-direction: column; min-width: 0; }

    .topbar {
      background: #fff;
      border-bottom: 1px solid var(--border);
      padding: 18px 32px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 30;
    }
    .topbar-left { display: flex; align-items: center; gap: 16px; }
    .hamburger {
      background: transparent; border: none; padding: 6px;
      border-radius: 8px; color: var(--text); display: none;
    }
    .hamburger:hover { background: var(--bg); }
    /* Desktop: when the sidebar is collapsed off-screen, the topbar hamburger
       becomes the only control that can reopen it. */
    .app.sidebar-collapsed .hamburger { display: inline-flex; }
    .page-title { font-size: 20px; font-weight: 700; color: var(--text); letter-spacing: -0.3px; }
    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .user-name { font-size: 14px; font-weight: 600; color: var(--text); }

    /* ── Content ── */
    .content {
      padding: 26px 32px 40px;
      display: grid;
      grid-template-columns: minmax(0, 1fr) 320px;
      gap: 22px;
      /* align-items defaults to stretch so the right column matches the
         left column's height; the Profile card uses flex:1 to fill any
         leftover space below Quick Links. */
    }
    .left-col  { display: flex; flex-direction: column; gap: 22px; min-width: 0; }
    .right-col { display: flex; flex-direction: column; gap: 22px; min-width: 0; }

    /* ── Cards ── */
    .card {
      background: var(--surface);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 22px 24px;
    }
    .card-header {
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px;
      margin-bottom: 18px;
    }
    .card-title { font-size: 18px; font-weight: 700; letter-spacing: -0.2px; color: var(--text); }
    .card-title.sm { font-size: 16px; }

    /* ── Membership Information ── */
    .info-card {
      background: linear-gradient(135deg, #eaf7f0 0%, #f6fbf8 45%, #ffffff 100%);
      border-radius: 22px;
      padding: 24px 26px;
      box-shadow: var(--shadow);
    }
    .info-head {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 20px; gap: 16px;
    }
    .info-title-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .info-title-row h2 { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
    .badge-active {
      background: var(--green); color: #fff;
      font-size: 12px; font-weight: 600;
      padding: 4px 12px; border-radius: 999px;
      letter-spacing: 0.2px;
    }
    .renewal-block { text-align: right; }
    .renewal-label { font-size: 12px; color: var(--text-muted); margin-bottom: 2px; }
    .renewal-date { font-size: 17px; font-weight: 700; letter-spacing: -0.2px; color: var(--green); }
    .renewal-remaining { font-size: 11px; color: #ef4444; margin-top: 2px; }

    /* ── Expired state ── */
    .info-card.is-expired {
      background: linear-gradient(135deg, #fde6e6 0%, #fcefef 45%, #ffffff 100%);
      border: 1px solid #f5c2c7;
    }
    .info-card.is-expired .badge-active {
      background: #dc2626;
      box-shadow: 0 2px 8px rgba(220,38,38,0.25);
    }
    .info-card.is-expired .renewal-date { color: #b91c1c; }
    .info-card.is-expired .renewal-remaining { color: #b91c1c; }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
    }
    .info-tile {
      background: #fff;
      border-radius: 14px;
      padding: 16px 16px 18px;
      box-shadow: 0 2px 10px rgba(15,23,42,0.04);
      min-height: 120px;
      display: flex; flex-direction: column; justify-content: space-between;
    }
    .info-tile .tile-head {
      display: flex; justify-content: space-between; align-items: center;
      color: var(--text-muted); font-size: 12px; font-weight: 500;
    }
    .info-tile .tile-icon {
      width: 30px; height: 30px; border-radius: 50%;
      background: #fff; color: #64748b;
      border: 1px solid #e6ebe8;
      display: flex; align-items: center; justify-content: center;
    }
    .info-tile .tile-icon svg { width: 14px; height: 14px; stroke-width: 1.8; }
    .info-tile .tile-value {
      font-size: 18px; font-weight: 700; letter-spacing: -0.3px;
      margin-top: 10px;
    }
    .info-tile.featured {
      background: linear-gradient(236.81deg, #085241 7.25%, #23BB97 91.07%);
      color: #fff;
      box-shadow: 0 8px 22px rgba(13,122,82,0.25);
      /* Extra bottom room so the white pill button (and its shadow) clears
         the green block edge instead of touching it. */
      padding-bottom: 20px;
    }
    .info-tile.featured .tile-head { color: rgba(255,255,255,0.9); font-size: 12.5px; }
    .info-tile.featured .tile-icon {
      background: rgba(255,255,255,0.2);
      color: #fff;
      border-color: rgba(255,255,255,0.25);
    }
    .info-tile.featured .tile-value { color: #fff; font-size: 18px; }
    .btn-change {
      margin-top: 12px;
      background: #fff;
      color: #1f2937;
      border: none;
      font-size: 12px;
      font-weight: 600;
      padding: 8px 14px;
      border-radius: 999px;
      align-self: flex-start;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      transition: background .15s, transform .15s, box-shadow .15s;
    }
    .btn-change svg { width: 12px; height: 12px; stroke-width: 2; color: #4b5563; }
    .btn-change:hover {
      background: #f3f4f6;
      transform: translateY(-1px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    }

    /* ── Form ── */
    .form-actions { display: flex; gap: 18px; flex-shrink: 0; align-items: center; }
    .btn-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 14px; font-weight: 600;
      color: var(--green);
      background: transparent;
      border: none;
      padding: 4px 2px;
      transition: color .15s, opacity .15s;
    }
    .btn-link:hover { color: var(--green-dark); }
    .btn-link svg { width: 14px; height: 14px; stroke-width: 2; }
    .btn-link.is-active { opacity: 0.7; }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px 20px;
    }
    .field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .field label {
      font-size: 12px;
      font-weight: 500;
      color: #475569;
    }
    .field label .req { color: var(--green); margin-left: 2px; }

    .same-as-primary {
      display: inline-block;
      margin-top: 16px;
      font-size: 13px;
      font-weight: 500;
      color: var(--green);
      text-decoration: underline;
      cursor: pointer;
      transition: color .15s;
    }
    .same-as-primary:hover { color: var(--green-dark); }
    .field input, .field select {
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
    .field input::placeholder { color: #9ca3af; }
    .field input:focus, .field select:focus {
      background: #fff;
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(13,122,82,0.12);
    }
    .field input:read-only { cursor: default; }
    /* Identity fields stay locked even in edit mode — show a not-allowed cursor
       and a muted look so it's clear they can't be changed here. */
    .field input[readonly]:not([data-editable]) { cursor: not-allowed; color: #6b7280; }
    /* An editable field that has been unlocked (edit mode on). */
    .field input[data-editable]:not([readonly]) { background: #fff; border-color: #d1d5db; }
    .form-note {
      grid-column: 1 / -1;
      display: flex; align-items: center; gap: 7px;
      margin-top: 4px;
      font-size: 12.5px; color: var(--text-muted);
    }
    .form-note svg { width: 14px; height: 14px; flex-shrink: 0; }

    .save-banner {
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
    .save-banner.visible { display: flex; }
    .save-banner svg { width: 16px; height: 16px; }

    /* ── Profile card (right) ── */
    .profile-card {
      background: linear-gradient(180deg, #ffffff 0%, #ffffff 55%, #e8f5ef 100%);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 22px 22px 28px;
      text-align: center;
    }
    /* Stretch the profile card so its bottom aligns with the left column.
       The right-col is a flex column; flex:1 lets the card consume the
       leftover height once Quick Links has its natural size. The avatar
       and text stay centred vertically inside the expanded box. */
    .profile-card-fill {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .profile-card-fill .avatar-circle {
      margin-top: auto;
    }
    .profile-card-fill .profile-sub {
      margin-bottom: auto;
    }
    .profile-card-title {
      text-align: left;
      font-size: 16px;
      font-weight: 700;
      color: var(--text);
      letter-spacing: -0.2px;
      margin-bottom: 16px;
    }
    .avatar-circle {
      width: 110px; height: 110px;
      border-radius: 50%;
      background: #d9dee3;
      margin: 8px auto 14px;
      display: flex; align-items: center; justify-content: center;
      color: #94a3b8;
      overflow: hidden;
    }
    .avatar-circle svg { width: 78px; height: 78px; }
    .profile-name { font-size: 19px; font-weight: 700; letter-spacing: -0.2px; }
    .profile-sub  { font-size: 12.5px; color: var(--text-muted); margin-top: 3px; }

    /* ── Quick Links ── */
    .ql-list { display: flex; flex-direction: column; gap: 4px; }
    .ql-item {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 4px;
      color: var(--text);
      font-size: 14px; font-weight: 500;
      border-bottom: 1px solid var(--border);
      transition: color .15s;
    }
    .ql-item:last-child { border-bottom: none; }
    .ql-item:hover { color: var(--green); }
    .ql-icon {
      width: 32px; height: 32px; border-radius: 10px;
      background: #f1f5f4; color: #475569;
      display: inline-flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .ql-icon svg { width: 16px; height: 16px; stroke-width: 1.8; }
    .ql-arrow { margin-left: auto; color: var(--text-muted); }
    .ql-arrow svg { width: 14px; height: 14px; stroke-width: 1.8; }

    /* ── Sidebar overlay ── */
    .sidebar-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 45; opacity: 0; visibility: hidden;
      transition: opacity .3s ease, visibility .3s ease;
    }
    .sidebar-overlay.open { opacity: 1; visibility: visible; }

    /* ── Bottom nav (mobile) ── */
    .bottom-nav {
      display: none;
      position: fixed; left: 0; right: 0; bottom: 0;
      background: #fff;
      border-top: 1px solid var(--border);
      padding: 10px 8px calc(10px + env(safe-area-inset-bottom));
      z-index: 40;
      box-shadow: 0 -4px 20px rgba(15,23,42,0.06);
      justify-content: space-around; align-items: center;
    }
    .bn-item {
      display: flex; flex-direction: column; align-items: center; gap: 4px;
      color: var(--text-muted);
      font-size: 11px; font-weight: 500;
      padding: 4px 10px; border-radius: 10px;
      flex: 1; max-width: 80px;
      transition: color .15s;
    }
    .bn-item svg { width: 20px; height: 20px; stroke-width: 1.8; }
    .bn-item.active { color: var(--green); }
    .bn-item.active .bn-icon {
      background: linear-gradient(135deg, #0e7a52 0%, #0a5a3d 100%);
      color: #fff;
      box-shadow: 0 4px 12px rgba(13,122,82,0.3);
    }
    .bn-icon {
      width: 36px; height: 36px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      transition: background .2s, color .2s;
    }

    /* ── Responsive ── */
    @media (max-width: 1180px) {
      .content { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 960px) {
      .info-grid { grid-template-columns: repeat(2, 1fr); }
      .form-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
      .app { grid-template-columns: 1fr; }
      .sidebar {
        position: fixed; left: 0; top: 0;
        width: 80%; max-width: 320px; height: 100vh;
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
      .info-card { padding: 20px; }
      .card-header { flex-wrap: wrap; }
      .form-actions { width: 100%; }
      .form-actions .btn { flex: 1; justify-content: center; }
      .bottom-nav { display: flex; }
      body { padding-bottom: 78px; }
    }
    .mobile-edit-link { display: none; }
    .spouse-profile { background: linear-gradient(180deg, #ffffff 0%, #ffffff 55%, #fdf2dc 100%); }

    @media (max-width: 768px) {
      .content { display: flex; flex-direction: column; gap: 16px; }
      .left-col, .right-col { display: contents; }

      .info-card                                { order: 1; }
      .right-col > .profile-card:nth-of-type(1) { order: 2; }
      .left-col  > .card:nth-of-type(1)         { order: 3; }
      .right-col > .profile-card:nth-of-type(2) { order: 4; }
      .left-col  > .card:nth-of-type(2)         { order: 5; }
      .right-col > .card                        { order: 6; }
    }

    @media (max-width: 520px) {
      html, body, .app, .main { max-width: 100%; overflow-x: hidden; }
      .info-card, .card, .profile-card { max-width: 100%; min-width: 0; }
      .content { padding: 14px 14px 28px; gap: 14px; }
      .info-card { padding: 16px; }
      .card { padding: 18px; }

      /* Header — side by side */
      .info-head {
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 14px;
        flex-wrap: nowrap;
      }
      .info-title-row {
        flex-direction: row; align-items: center; gap: 8px;
        flex-wrap: wrap; min-width: 0; flex: 1 1 auto;
      }
      .info-title-row h2 { font-size: 15px; line-height: 1.2; }
      .badge-active { font-size: 10.5px; padding: 3px 10px; }
      .renewal-block { text-align: right; flex-shrink: 0; min-width: 0; }
      .renewal-label { font-size: 10.5px; white-space: nowrap; }
      .renewal-date { font-size: 13px; white-space: nowrap; }
      .renewal-remaining { font-size: 10px; }

      .info-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
      .info-tile { padding: 12px 14px 14px; min-height: auto; border-radius: 12px; overflow: hidden; }
      .info-tile .tile-head { font-size: 10.5px; }
      .info-tile .tile-icon { width: 24px; height: 24px; }
      .info-tile .tile-icon svg { width: 11px; height: 11px; }
      .info-tile .tile-value {
        font-size: 14px; margin-top: 8px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
      }
      .info-tile.featured .tile-value { font-size: 14px; }
      .info-tile.featured .btn-change { display: none; }

      /* Profile card mobile */
      .profile-card { padding: 22px 22px 26px; }
      .profile-card .avatar-circle { width: 150px; height: 150px; margin: 14px auto 18px; }
      .profile-card .avatar-circle svg { width: 105px; height: 105px; }
      .profile-card .profile-name { font-size: 26px; font-weight: 700; }
      .profile-card .profile-sub  { font-size: 14px; margin-top: 4px; }
      .profile-card .mobile-edit-link {
        display: inline-flex;
        align-items: center; gap: 6px;
        margin-top: 16px;
        color: #d97706;
        font-size: 15px; font-weight: 600;
        background: transparent; border: none;
      }
      .profile-card .mobile-edit-link svg { width: 16px; height: 16px; stroke-width: 2; }

      .form-grid { grid-template-columns: 1fr; gap: 14px; }
      .form-actions { width: auto; gap: 14px; }
      .card .card-header .btn-link:first-of-type { display: none; }
      .page-title { font-size: 18px; }
    }
    @media (max-width: 380px) {
      .info-card { padding: 14px; }
      .info-title-row h2 { font-size: 14px; }
      .profile-card .avatar-circle { width: 130px; height: 130px; }
      .profile-card .avatar-circle svg { width: 92px; height: 92px; }
      .profile-card .profile-name { font-size: 22px; }
    }
    @media (max-width: 380px) {
      .topbar { padding: 12px 14px; }
      .content { padding: 14px 12px 28px; }
      .bn-item { font-size: 10px; }
      .bn-icon { width: 32px; height: 32px; }
    }
  </style>
</head>
<body>

@php
  /** @var \App\Support\MemberProfile $profile */
@endphp

<div class="app">

  @include('member-portal.partials.sidebar', ['active' => 'profile', 'mode' => 'links'])

  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  {{-- ── Main ── --}}
  <div class="main">

    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()" aria-label="Open menu">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <h1 class="page-title">Profile</h1>
      </div>
      <div class="topbar-right">
        <span class="user-name">{{ ($profile->fullName ?? null) ?: 'Member' }}</span>
      </div>
    </header>

    <section class="content">

      {{-- ── LEFT main column ── --}}
      <div class="left-col">

        {{-- Membership Information --}}
        <div class="info-card {{ $profile->isExpired() ? 'is-expired' : '' }}">
          <div class="info-head">
            <div class="info-title-row">
              <h2>Membership Information</h2>
              <span class="badge-active">{{ $profile->isExpired() ? 'Expired' : $profile->status }}</span>
            </div>
            <div class="renewal-block">
              <div class="renewal-label">{{ str_contains(strtolower($profile->level ?? ''), 'checkomatic') ? 'Next Payment Due' : 'Next Renewal' }}</div>
              <div class="renewal-date">{{ $profile->renewalFormatted() ?: '—' }}</div>
              @if($profile->daysLeft() !== null)
              <div class="renewal-remaining">{{ $profile->daysLeft() }} days remaining</div>
              @endif
            </div>
          </div>

          <div class="info-grid">
            <div class="info-tile featured">
              <div>
                <div class="tile-head">
                  <span>Membership Type</span>
                  <span class="tile-icon">
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                  </span>
                </div>
                <div class="tile-value">{{ $profile->level ?: '—' }}</div>
              </div>
              <button type="button" class="btn-change ql-change-level">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                Change Membership Level
              </button>
            </div>

            <div class="info-tile">
              <div class="tile-head">
                <span>Member Since</span>
                <span class="tile-icon">
                  <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
              </div>
              <div class="tile-value">{{ $profile->memberSinceFormatted() ?: '—' }}</div>
            </div>

            <div class="info-tile">
              <div class="tile-head">
                <span>Yearly Contribution</span>
                <span class="tile-icon">
                  <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </span>
              </div>
              <div class="tile-value">{{ $profile->yearlyFee ?: '—' }}</div>
            </div>

            <div class="info-tile">
              <div class="tile-head">
                <span>TXDL#/ TXID#</span>
                <span class="tile-icon">
                  <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="7" y1="15" x2="11" y2="15"/></svg>
                </span>
              </div>
              <div class="tile-value">{{ $profile->txId ?: '—' }}</div>
            </div>
          </div>
        </div>

        {{-- Primary Member Information --}}
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Primary Member Information</h3>
            <div class="form-actions">
              <button type="button" class="btn-link" id="btnEditPrimary">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                <span>Edit Profile</span>
              </button>
              <button type="button" class="btn-link" id="btnSavePrimary">
                <span>Save Changes</span>
              </button>
            </div>
          </div>

          <div class="save-banner" id="savePrimaryBanner">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            Changes saved successfully.
          </div>

          <form class="form-grid" id="formPrimary" novalidate>
            <div class="field">
              <label for="p-first">First Name<span class="req">*</span></label>
              <input type="text" id="p-first" value="{{ $profile->firstName }}" readonly />
            </div>
            <div class="field">
              <label for="p-last">Last Name<span class="req">*</span></label>
              <input type="text" id="p-last" value="{{ $profile->lastName }}" readonly />
            </div>
            <div class="field">
              <label for="p-email">Email Address<span class="req">*</span></label>
              <input type="email" id="p-email" value="{{ $profile->email }}" readonly />
            </div>
            <div class="field">
              <label for="p-phone">Phone Number<span class="req">*</span></label>
              <input type="tel" id="p-phone" value="{{ $profile->phone }}" placeholder="(000) 000-0000" readonly />
            </div>
            <div class="field" style="grid-column: 1 / -1;">
              <label for="p-street">Street Address<span class="req">*</span></label>
              <input type="text" id="p-street" value="{{ $profile->street }}" data-editable readonly />
            </div>
            <div class="field">
              <label for="p-tx">TX DL # / TX ID #<span class="req">*</span></label>
              <input type="text" id="p-tx" value="{{ $profile->txId }}" readonly />
            </div>
            <div class="field">
              <label for="p-dob">Date of Birth<span class="req">*</span></label>
              <input type="date" id="p-dob" value="{{ $profile->dobInput() }}" readonly />
            </div>
            <div class="field">
              <label for="p-zip">Zip Code<span class="req">*</span></label>
              <input type="text" id="p-zip" value="{{ $profile->zip }}" data-editable readonly />
            </div>
            <div class="field">
              <label for="p-city">City<span class="req">*</span></label>
              <input type="text" id="p-city" value="{{ $profile->city }}" data-editable readonly />
            </div>
            <div class="field">
              <label for="p-state">States<span class="req">*</span></label>
              <input type="text" id="p-state" value="{{ $profile->state }}" data-editable readonly />
            </div>
            <div class="field">
              <label for="p-zone">Center / Zone<span class="req">*</span></label>
              <input type="text" id="p-zone" value="{{ $profile->zone }}" placeholder="Spring Branch Islamic Center" readonly />
            </div>
            <p class="form-note">
              <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              Only address fields can be edited here. To change your name, email, or phone number, please contact ISGH.
            </p>
          </form>
        </div>

        {{-- Family Members --}}
        @foreach($profile->family as $famIndex => $famMember)
        @php $famTitle = $famMember->role !== '' ? $famMember->role : 'Family Member'; @endphp
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">{{ $famTitle }} Information</h3>
            <div class="form-actions">
              <button type="button" class="btn-link" id="btnEditFamily{{ $famIndex }}">
                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                <span>Edit Profile</span>
              </button>
              <button type="button" class="btn-link" id="btnSaveFamily{{ $famIndex }}">
                <span>Save Changes</span>
              </button>
            </div>
          </div>

          <div class="save-banner" id="saveFamilyBanner{{ $famIndex }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            {{ $famTitle }} details saved.
          </div>

          <form class="form-grid" id="formFamily{{ $famIndex }}" novalidate>
            <div class="field"><label for="f{{ $famIndex }}-first">First Name<span class="req">*</span></label><input type="text" id="f{{ $famIndex }}-first" value="{{ $famMember->firstName }}" readonly /></div>
            <div class="field"><label for="f{{ $famIndex }}-last">Last Name<span class="req">*</span></label><input type="text" id="f{{ $famIndex }}-last" value="{{ $famMember->lastName }}" readonly /></div>
            <div class="field"><label for="f{{ $famIndex }}-email">Email Address<span class="req">*</span></label><input type="email" id="f{{ $famIndex }}-email" value="{{ $famMember->email }}" readonly /></div>
            <div class="field"><label for="f{{ $famIndex }}-phone">Phone Number<span class="req">*</span></label><input type="tel" id="f{{ $famIndex }}-phone" value="{{ $famMember->phone }}" readonly /></div>
            <div class="field" style="grid-column: 1 / -1;"><label for="f{{ $famIndex }}-address">Street Address<span class="req">*</span></label><input type="text" id="f{{ $famIndex }}-address" value="{{ $famMember->street }}" data-editable readonly /></div>
            <div class="field"><label for="f{{ $famIndex }}-tx">TX DL # / TX ID #<span class="req">*</span></label><input type="text" id="f{{ $famIndex }}-tx" value="{{ $famMember->txId }}" readonly /></div>
            <div class="field"><label for="f{{ $famIndex }}-dob">Date of Birth<span class="req">*</span></label><input type="date" id="f{{ $famIndex }}-dob" value="{{ $famMember->dobInput() }}" readonly /></div>
            <div class="field"><label for="f{{ $famIndex }}-zip">Zip Code<span class="req">*</span></label><input type="text" id="f{{ $famIndex }}-zip" value="{{ $famMember->zip }}" data-editable readonly /></div>
            <div class="field"><label for="f{{ $famIndex }}-city">City<span class="req">*</span></label><input type="text" id="f{{ $famIndex }}-city" value="{{ $famMember->city }}" data-editable readonly /></div>
            <div class="field"><label for="f{{ $famIndex }}-state">States<span class="req">*</span></label><input type="text" id="f{{ $famIndex }}-state" value="{{ $famMember->state }}" data-editable readonly /></div>
            <div class="field"><label for="f{{ $famIndex }}-zone">Center / Zone<span class="req">*</span></label><input type="text" id="f{{ $famIndex }}-zone" value="{{ $famMember->zone }}" readonly /></div>
          </form>

          <a href="#" class="same-as-primary" data-same-as-primary="{{ $famIndex }}">
            Street Address, states, zip code, city is same as primary member
          </a>
        </div>
        @endforeach

      </div>

      {{-- ── RIGHT column ── --}}
      <aside class="right-col">

        {{-- Quick Links --}}
        <div class="card">
          <div class="card-header" style="margin-bottom:8px;">
            <h3 class="card-title">Quick Links</h3>
          </div>
          <div class="ql-list">
            <a href="#" class="ql-item ql-renew-link">
              <span class="ql-icon"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></span>
              Renew Membership
              <span class="ql-arrow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></span>
            </a>
            <a href="{{ route('member-portal.payments') }}" class="ql-item">
              <span class="ql-icon"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
              View Invoices
              <span class="ql-arrow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></span>
            </a>
            <a href="{{ route('member-portal.payments') }}" class="ql-item">
              <span class="ql-icon"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
              Payment History
              <span class="ql-arrow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></span>
            </a>
            <a href="#" class="ql-item ql-change-level">
              <span class="ql-icon"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
              Change Level
              <span class="ql-arrow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></span>
            </a>
          </div>
        </div>

        {{-- Primary Profile card --}}
        <div class="profile-card profile-card-fill">
          <div class="profile-card-title">Profile</div>
          <div class="avatar-circle">
            <svg fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>
            </svg>
          </div>
          <div class="profile-name">{{ $profile->fullName ?: 'Member' }}</div>
          <div class="profile-sub">{{ $profile->level ?: 'Member' }}</div>
        </div>

        {{-- Family Member Profile cards --}}
        @foreach($profile->family as $famIndex => $famMember)
        @php $famTitle = $famMember->role !== '' ? $famMember->role : 'Family Member'; @endphp
        <div class="profile-card profile-card-fill">
          <div class="profile-card-title">{{ $famTitle }} Information</div>
          <div class="avatar-circle">
            <svg fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>
            </svg>
          </div>
          <div class="profile-name" id="familyNameLabel{{ $famIndex }}">{{ $famMember->fullName }}</div>
          <div class="profile-sub">{{ $profile->level ?: 'Member' }}</div>
        </div>
        @endforeach

      </aside>

    </section>
  </div>
</div>

@include('member-portal.partials.bottom-nav', ['active' => 'profile', 'mode' => 'links'])

<script>
  (function () {
    // ── Sidebar / hamburger toggle ─────────────────────────────────────────
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
      link.addEventListener('click', () => { if (isMobile()) closeMobileDrawer(); });
    });

    // ── Form edit / save ───────────────────────────────────────────────────
    function setupForm({ formId, editBtnId, saveBtnId, bannerId }) {
      const form    = document.getElementById(formId);
      const editBtn = document.getElementById(editBtnId);
      const saveBtn = document.getElementById(saveBtnId);
      const banner  = document.getElementById(bannerId);
      if (!form || !editBtn || !saveBtn) return;

      let editing = false;
      const inputs = () => form.querySelectorAll('input, select');
      // Only address fields are member-editable. Identity fields (name, email,
      // phone, DOB, TX ID, zone) stay read-only — they are managed by ISGH.
      const editableInputs = () => form.querySelectorAll('[data-editable]');

      function setEditing(state) {
        editing = state;
        editableInputs().forEach(el => { el.readOnly = !state; });
        editBtn.textContent = state ? 'Cancel' : editBtn.dataset.label || editBtn.textContent.trim();
        if (!editBtn.dataset.label) editBtn.dataset.label = 'Edit Profile';
        // Re-render the edit button text + icon if cancelled
        if (!state) {
          editBtn.innerHTML = '<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg> ' + (editBtn.dataset.label || 'Edit');
        }
      }

      editBtn.addEventListener('click', () => setEditing(!editing));

      saveBtn.addEventListener('click', async () => {
        if (saveBtn.disabled) return;
        // Light validation: email format if provided
        let valid = true;
        inputs().forEach(el => {
          if (el.type === 'email' && el.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value)) {
            el.style.borderColor = '#ef4444';
            el.style.background = '#fef2f2';
            valid = false;
          } else {
            el.style.borderColor = '';
            el.style.background = '';
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
              setEditing(false);
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
        setEditing(false);
        if (banner) {
          banner.classList.add('visible');
          setTimeout(() => banner.classList.remove('visible'), 2500);
        }
      });
    }

    setupForm({ formId: 'formPrimary', editBtnId: 'btnEditPrimary', saveBtnId: 'btnSavePrimary', bannerId: 'savePrimaryBanner' });

    // ── Family member forms (one per associated family member) ────────────
    document.querySelectorAll('[id^="formFamily"]').forEach(form => {
      const idx = form.id.replace('formFamily', '');
      setupForm({
        formId:   form.id,
        editBtnId: 'btnEditFamily' + idx,
        saveBtnId: 'btnSaveFamily' + idx,
        bannerId:  'saveFamilyBanner' + idx,
      });

      // "Same as primary member" link for this family member
      const sameLink = document.querySelector('[data-same-as-primary="' + idx + '"]');
      if (sameLink) {
        sameLink.addEventListener('click', (e) => {
          e.preventDefault();
          const map = {
            'p-street': 'f' + idx + '-address',
            'p-city':   'f' + idx + '-city',
            'p-state':  'f' + idx + '-state',
            'p-zip':    'f' + idx + '-zip',
          };
          Object.entries(map).forEach(([from, to]) => {
            const src = document.getElementById(from);
            const dst = document.getElementById(to);
            if (src && dst) dst.value = src.value;
          });
        });
      }

      // Mirror first+last to this family member's profile card name
      const fFirst = document.getElementById('f' + idx + '-first');
      const fLast  = document.getElementById('f' + idx + '-last');
      const fLabel = document.getElementById('familyNameLabel' + idx);
      function syncFamilyName() {
        if (!fLabel) return;
        const name = ((fFirst?.value || fFirst?.placeholder || '') + ' ' + (fLast?.value || fLast?.placeholder || '')).trim();
        if (name) fLabel.textContent = name;
      }
      fFirst?.addEventListener('input', syncFamilyName);
      fLast?.addEventListener('input', syncFamilyName);
      syncFamilyName();
    });
  })();
</script>

@include('member-portal.partials.renew-modal')
@include('member-portal.partials.level-modal')

</body>
</html>
