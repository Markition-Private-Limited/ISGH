{{-- ── LEFT main column ── --}}
<div class="left-col">

  {{-- Membership Information --}}
  <div class="info-card">
    <div class="info-head">
      <div class="info-title-row">
        <h2>Membership Information</h2>
        <span class="badge-active">{{ $profile->isExpired() ? 'Expired' : $profile->status }}</span>
      </div>
      <div class="renewal-block">
        <div class="renewal-label">Next Renewal</div>
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
        <button type="button" class="btn-change">
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
        <div class="tile-value">{{ $profile->yearlyFee ?: '$200.00' }}</div>
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
      <div class="field"><label for="p-first">First Name<span class="req">*</span></label><input type="text" id="p-first" value="{{ $profile->firstName }}" readonly /></div>
      <div class="field"><label for="p-last">Last Name<span class="req">*</span></label><input type="text" id="p-last" value="{{ $profile->lastName }}" readonly /></div>
      <div class="field"><label for="p-email">Email Address<span class="req">*</span></label><input type="email" id="p-email" value="{{ $profile->email }}" readonly /></div>
      <div class="field"><label for="p-phone">Phone Number<span class="req">*</span></label><input type="tel" id="p-phone" value="{{ $profile->phone }}" placeholder="(000) 000-0000" readonly /></div>
      <div class="field" style="grid-column: 1 / -1;"><label for="p-street">Street Address<span class="req">*</span></label><input type="text" id="p-street" value="{{ $profile->street }}" readonly /></div>
      <div class="field"><label for="p-tx">TX DL # / TX ID #<span class="req">*</span></label><input type="text" id="p-tx" value="{{ $profile->txId }}" readonly /></div>
      <div class="field"><label for="p-dob">Date of Birth<span class="req">*</span></label><input type="date" id="p-dob" value="{{ $profile->dob }}" readonly /></div>
      <div class="field"><label for="p-zip">Zip Code<span class="req">*</span></label><input type="text" id="p-zip" value="{{ $profile->zip }}" readonly /></div>
      <div class="field"><label for="p-city">City<span class="req">*</span></label><input type="text" id="p-city" value="{{ $profile->city }}" readonly /></div>
      <div class="field"><label for="p-state">States<span class="req">*</span></label><input type="text" id="p-state" value="{{ $profile->state }}" readonly /></div>
      <div class="field"><label for="p-zone">Center / Zone<span class="req">*</span></label><input type="text" id="p-zone" value="{{ $profile->zone }}" placeholder="Spring Branch Islamic Center" readonly /></div>
    </form>
  </div>

  {{-- Spouse Information --}}
  @if($profile->hasSpouse())
  @php $spouse = $profile->spouse(); @endphp
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Spouse Information</h3>
      <div class="form-actions">
        <button type="button" class="btn-link" id="btnEditSpouse">
          <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
          <span>Edit Profile</span>
        </button>
        <button type="button" class="btn-link" id="btnSaveSpouse">
          <span>Save Changes</span>
        </button>
      </div>
    </div>

    <div class="save-banner" id="saveSpouseBanner">
      <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
      Spouse details saved.
    </div>

    <form class="form-grid" id="formSpouse" novalidate>
      <div class="field"><label for="s-first">First Name<span class="req">*</span></label><input type="text" id="s-first" value="{{ $spouse->firstName }}" readonly /></div>
      <div class="field"><label for="s-last">Last Name<span class="req">*</span></label><input type="text" id="s-last" value="{{ $spouse->lastName }}" readonly /></div>
      <div class="field"><label for="s-email">Email Address<span class="req">*</span></label><input type="email" id="s-email" value="{{ $spouse->email }}" readonly /></div>
      <div class="field"><label for="s-phone">Phone Number<span class="req">*</span></label><input type="tel" id="s-phone" value="{{ $spouse->phone }}" readonly /></div>
      <div class="field" style="grid-column: 1 / -1;"><label for="s-address">Street Address<span class="req">*</span></label><input type="text" id="s-address" value="{{ $spouse->street }}" readonly /></div>
      <div class="field"><label for="s-tx">TX DL # / TX ID #<span class="req">*</span></label><input type="text" id="s-tx" value="{{ $spouse->txId }}" readonly /></div>
      <div class="field"><label for="s-dob">Date of Birth<span class="req">*</span></label><input type="date" id="s-dob" value="{{ $spouse->dob }}" readonly /></div>
      <div class="field"><label for="s-zip">Zip Code<span class="req">*</span></label><input type="text" id="s-zip" value="{{ $spouse->zip }}" readonly /></div>
      <div class="field"><label for="s-city">City<span class="req">*</span></label><input type="text" id="s-city" value="{{ $spouse->city }}" readonly /></div>
      <div class="field"><label for="s-state">States<span class="req">*</span></label><input type="text" id="s-state" value="{{ $spouse->state }}" readonly /></div>
      <div class="field"><label for="s-zone">Center / Zone<span class="req">*</span></label><input type="text" id="s-zone" value="{{ $spouse->zone }}" readonly /></div>
    </form>

    <a href="#" class="same-as-primary" id="sameAsPrimary">
      Street Address, states, zip code, city is same as primary member
    </a>
  </div>
  @endif

</div>

{{-- ── RIGHT column ── --}}
<aside class="right-col">

  {{-- Quick Links --}}
  <div class="card">
    <div class="card-header" style="margin-bottom:8px;">
      <h3 class="card-title">Quick Links</h3>
    </div>
    <div class="ql-list">
      <a href="#" class="ql-item">
        <span class="ql-icon"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></span>
        Renew Membership
        <span class="ql-arrow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></span>
      </a>
      <a href="#" class="ql-item">
        <span class="ql-icon"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
        View Invoices
        <span class="ql-arrow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></span>
      </a>
      <a href="#" class="ql-item">
        <span class="ql-icon"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
        Payment History
        <span class="ql-arrow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></span>
      </a>
      <a href="#" class="ql-item">
        <span class="ql-icon"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
        Change Level
        <span class="ql-arrow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></span>
      </a>
    </div>
  </div>

  {{-- Primary Profile card --}}
  <div class="profile-card">
    <div class="profile-card-title">Profile</div>
    <div class="avatar-circle">
      <svg fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>
      </svg>
    </div>
    <div class="profile-name">{{ $profile->fullName ?: 'Member' }}</div>
    <div class="profile-sub">Individual Member of ISGH</div>
    <button type="button" class="mobile-edit-link" onclick="document.getElementById('btnEditPrimary')?.click(); document.getElementById('formPrimary')?.scrollIntoView({behavior:'smooth', block:'start'});">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
      Edit Profile
    </button>
  </div>

  {{-- Spouse Profile card --}}
  @if($profile->hasSpouse())
  <div class="profile-card spouse-profile">
    <div class="profile-card-title">Spouse Information</div>
    <div class="avatar-circle">
      <svg fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>
      </svg>
    </div>
    <div class="profile-name" id="spouseNameLabel">{{ $profile->spouse()->fullName }}</div>
    <div class="profile-sub">Individual Member of ISGH</div>
    <button type="button" class="mobile-edit-link" onclick="document.getElementById('btnEditSpouse')?.click(); document.getElementById('formSpouse')?.scrollIntoView({behavior:'smooth', block:'start'});">
      <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
      Edit Profile
    </button>
  </div>
  @endif

</aside>
