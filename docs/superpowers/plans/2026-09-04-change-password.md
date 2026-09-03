# Change Password Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Change Password" modal to the Staff Portal so any authenticated admin can update their own password at any time.

**Architecture:** A `POST /admin/change-password` route calls a new `PortalController::changePassword()` method that validates current password, new password, and confirmation, then updates the hash. The modal lives in `layouts/app.blade.php` (rendered on every portal page) and is triggered from the topbar role dropdown. On validation failure the modal auto-reopens via a small inline script that checks for password-related session errors.

**Tech Stack:** Laravel 11, Blade, plain JS (no additional libraries), `Illuminate\Support\Facades\Hash`, `Illuminate\Support\Facades\Auth`.

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `routes/web.php` | Modify | Add `POST /admin/change-password` route inside auth middleware group |
| `app/Http/Controllers/PortalController.php` | Modify | Add `changePassword()` method |
| `resources/views/layouts/app.blade.php` | Modify | Add dropdown item, modal HTML, and open/close JS |

---

### Task 1: Add the route

**Files:**
- Modify: `routes/web.php:52-58`

- [ ] **Step 1: Add the route inside the auth middleware group**

Open `routes/web.php`. Inside the `Route::middleware(['auth', 'active.user'])->group(...)` block (currently lines 52–58), add the change-password POST route:

```php
Route::middleware(['auth', 'active.user'])->group(function () {
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/members', [PortalController::class, 'members'])->name('members');
    Route::get('/members/export/csv', [PortalController::class, 'exportCsv'])->name('members.export.csv');
    Route::get('/members/export/pdf', [PortalController::class, 'exportPdf'])->name('members.export.pdf');
    Route::get('/members/print', [PortalController::class, 'printable'])->name('members.print');
    Route::post('/change-password', [PortalController::class, 'changePassword'])->name('change-password');
});
```

- [ ] **Step 2: Verify route is registered**

Run:
```bash
php artisan route:list --name=portal.change-password
```
Expected output: one row showing `POST | admin/change-password | portal.change-password`.

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: add POST /admin/change-password route"
```

---

### Task 2: Add the controller method

**Files:**
- Modify: `app/Http/Controllers/PortalController.php`

Add this method anywhere in `PortalController` (e.g. after the `logout()` method, before `dashboard()`). The `Hash` facade is already imported via `Illuminate\Support\Facades\Auth`; add the `Hash` import too.

- [ ] **Step 1: Add the Hash import at the top of the controller**

The file already has:
```php
use Illuminate\Support\Facades\Auth;
```

Add directly below it:
```php
use Illuminate\Support\Facades\Hash;
```

- [ ] **Step 2: Add the changePassword method**

Add this method after `logout()`:

```php
public function changePassword(Request $request)
{
    $request->validate([
        'current_password'      => ['required'],
        'new_password'          => ['required', 'min:8', 'confirmed'],
        'new_password_confirmation' => ['required'],
    ]);

    $user = Auth::user();

    if (! Hash::check($request->current_password, $user->password)) {
        return back()
            ->withErrors(['current_password' => 'The current password is incorrect.'])
            ->with('open_change_password_modal', true);
    }

    $user->update(['password' => $request->new_password]);

    return redirect()->back()->with('success', 'Password updated successfully.');
}
```

- [ ] **Step 3: Verify the app still boots**

Run:
```bash
php artisan route:list --name=portal.change-password
```
Expected: same row as before, no errors.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/PortalController.php
git commit -m "feat: add changePassword method to PortalController"
```

---

### Task 3: Add the modal and dropdown item to the layout

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

This task has three sub-steps: add the dropdown item, add the modal HTML, add the JS.

- [ ] **Step 1: Add "Change Password" to the role dropdown menu**

Find this block in `layouts/app.blade.php` (around line 172–178):
```html
<div class="role-dropdown-menu" id="role-menu" role="menu" aria-label="User menu">
  <hr style="border:none;border-top:1px solid var(--clr-border-light);margin:4px 0;" />
  <a class="role-dropdown-item" href="#" role="menuitem"
     onclick="event.preventDefault();showLogoutModal();">
    Sign Out
  </a>
</div>
```

Replace it with:
```html
<div class="role-dropdown-menu" id="role-menu" role="menu" aria-label="User menu">
  <a class="role-dropdown-item" href="#" role="menuitem"
     onclick="event.preventDefault();showChangePasswordModal();">
    Change Password
  </a>
  <hr style="border:none;border-top:1px solid var(--clr-border-light);margin:4px 0;" />
  <a class="role-dropdown-item" href="#" role="menuitem"
     onclick="event.preventDefault();showLogoutModal();">
    Sign Out
  </a>
</div>
```

- [ ] **Step 2: Add the modal HTML**

Find the closing `</script>` tag of the logout modal script (around line 256) — just before `</body>`. Add the change-password modal immediately after the logout modal's closing `</div>` (line 240) and before the existing logout `<script>` block:

```html
{{-- ── Change Password Modal ──────────────────────────────────── --}}
<div id="change-password-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
  {{-- Backdrop --}}
  <div onclick="hideChangePasswordModal()" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(2px);"></div>
  {{-- Dialog --}}
  <div style="position:relative;background:#fff;border-radius:16px;padding:32px 28px 24px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
    {{-- Icon --}}
    <div style="width:56px;height:56px;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
    </div>
    <div style="font-size:1.15rem;font-weight:700;color:#111827;margin-bottom:20px;text-align:center;">Change Password</div>

    <form method="POST" action="{{ route('portal.change-password') }}">
      @csrf

      {{-- Current Password --}}
      <div style="margin-bottom:16px;">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px;" for="cp_current">Current Password</label>
        <input
          type="password"
          id="cp_current"
          name="current_password"
          autocomplete="current-password"
          style="width:100%;padding:9px 12px;border:1px solid {{ $errors->has('current_password') ? '#ef4444' : '#d1d5db' }};border-radius:8px;font-size:.875rem;box-sizing:border-box;outline:none;"
        />
        @error('current_password')
          <div style="color:#ef4444;font-size:.78rem;margin-top:4px;">{{ $message }}</div>
        @enderror
      </div>

      {{-- New Password --}}
      <div style="margin-bottom:16px;">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px;" for="cp_new">New Password</label>
        <input
          type="password"
          id="cp_new"
          name="new_password"
          autocomplete="new-password"
          style="width:100%;padding:9px 12px;border:1px solid {{ $errors->has('new_password') ? '#ef4444' : '#d1d5db' }};border-radius:8px;font-size:.875rem;box-sizing:border-box;outline:none;"
        />
        @error('new_password')
          <div style="color:#ef4444;font-size:.78rem;margin-top:4px;">{{ $message }}</div>
        @enderror
      </div>

      {{-- Confirm New Password --}}
      <div style="margin-bottom:24px;">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px;" for="cp_confirm">Confirm New Password</label>
        <input
          type="password"
          id="cp_confirm"
          name="new_password_confirmation"
          autocomplete="new-password"
          style="width:100%;padding:9px 12px;border:1px solid {{ $errors->has('new_password_confirmation') ? '#ef4444' : '#d1d5db' }};border-radius:8px;font-size:.875rem;box-sizing:border-box;outline:none;"
        />
        @error('new_password_confirmation')
          <div style="color:#ef4444;font-size:.78rem;margin-top:4px;">{{ $message }}</div>
        @enderror
      </div>

      <div style="display:flex;gap:12px;">
        <button type="button" onclick="hideChangePasswordModal()" style="flex:1;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.875rem;font-weight:600;color:#374151;cursor:pointer;">
          Cancel
        </button>
        <button type="submit" style="flex:1;padding:10px;border:none;border-radius:8px;background:#3b82f6;font-size:.875rem;font-weight:600;color:#fff;cursor:pointer;">
          Update Password
        </button>
      </div>
    </form>
  </div>
</div>
```

- [ ] **Step 3: Add the JS open/close functions**

Find the existing `<script>` block that defines `showLogoutModal` / `hideLogoutModal` (around lines 242–256). Add the change-password functions inside the same block, and add the auto-open logic. Replace the entire `<script>` block with:

```html
<script>
function showLogoutModal() {
  var m = document.getElementById('logout-modal');
  m.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function hideLogoutModal() {
  var m = document.getElementById('logout-modal');
  m.style.display = 'none';
  document.body.style.overflow = '';
}

function showChangePasswordModal() {
  var m = document.getElementById('change-password-modal');
  m.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function hideChangePasswordModal() {
  var m = document.getElementById('change-password-modal');
  m.style.display = 'none';
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    hideLogoutModal();
    hideChangePasswordModal();
  }
});

// Auto-open change-password modal if validation errors were returned for it
@if($errors->hasAny(['current_password', 'new_password', 'new_password_confirmation']) || session('open_change_password_modal'))
document.addEventListener('DOMContentLoaded', function() {
  showChangePasswordModal();
});
@endif
</script>
```

- [ ] **Step 4: Clear view cache and verify the page loads**

```bash
php artisan view:clear
```

Then open `http://localhost/ISGH/public/admin/dashboard` in a browser while logged in. Confirm:
- The topbar role dropdown shows "Change Password" above "Sign Out".
- Clicking "Change Password" opens the modal.
- Clicking "Cancel" or pressing Escape closes the modal.

- [ ] **Step 5: Commit**

```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat: add change password modal to staff portal layout"
```

---

### Task 4: Manual end-to-end test

No automated test infrastructure exists in this project for browser flows; test manually.

- [ ] **Step 1: Test validation — wrong current password**

1. Open the modal.
2. Enter an incorrect current password, any new password (≥8 chars), matching confirmation.
3. Submit.
4. Expected: modal re-opens, field error "The current password is incorrect." shown under Current Password field.

- [ ] **Step 2: Test validation — new password too short**

1. Open the modal.
2. Enter correct current password, a 5-character new password, matching confirmation.
3. Submit.
4. Expected: modal re-opens, error under New Password saying it must be at least 8 characters.

- [ ] **Step 3: Test validation — passwords don't match**

1. Open the modal.
2. Enter correct current password, a valid new password, a different confirmation.
3. Submit.
4. Expected: modal re-opens, confirmation mismatch error shown.

- [ ] **Step 4: Test happy path**

1. Open the modal.
2. Enter correct current password, a new valid password, matching confirmation.
3. Submit.
4. Expected: modal closes, green success flash "Password updated successfully." shown on the page.
5. Log out and log back in with the new password to confirm the change persisted.

- [ ] **Step 5: Commit (no code changes — just verification complete)**

No commit needed for this task unless you discovered and fixed a bug during testing.
