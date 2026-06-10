# Checkomatic Level-Change Parity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restrict the change-level dropdown to 4 levels (Checkomatic, Individual, Lifetime Family, Lifetime Individual) and add full Checkomatic UX parity with the public signup flow (amount field with live note, recurring-billing warning, spouse-disclaimer modal, optional spouse screen).

**Architecture:** Two-file change. Backend: `LevelChangeService::availableLevels()` switches from "all minus current/flat" to an explicit 4-slug allowlist; `charge()` gains a server-side reject for `checkomatic_family`. Frontend: `level-modal.blade.php` gets an updated amount field (min $10, live note), a warning block, a spouse-disclaimer overlay, and JS changes so Checkomatic shows Screen 2 (spouse, optional) before Review.

**Tech Stack:** PHP 8/Laravel, Blade, vanilla JS (IIFE in the existing modal), PHPUnit

---

## File Map

| File | What changes |
|------|-------------|
| `app/Services/LevelChangeService.php` | `availableLevels()` uses allowlist; `charge()` rejects `checkomatic_family` |
| `resources/views/member-portal/partials/level-modal.blade.php` | Amount field (min 10, live note), warning block, disclaimer modal HTML+CSS, JS for Screen 2 Checkomatic path |
| `tests/Unit/LevelChangeServiceTest.php` | Update broken count assertions; add `checkomatic_family` reject test |

---

## Task 1: Update `availableLevels()` allowlist + fix existing tests

**Files:**
- Modify: `app/Services/LevelChangeService.php:58-80`
- Modify: `tests/Unit/LevelChangeServiceTest.php:27-53`

### Context

`availableLevels()` currently iterates `MembershipTypes::allSlugs()` (7 slugs) and skips only `$currentSlug` and `'flat'`. The new allowlist is `['checkomatic_individual', 'individual', 'lifetime_family', 'lifetime_individual']`. The label for `checkomatic_individual` must be `'Checkomatic'` (not the existing `MembershipTypes::labelForSlug()` value of `'Checkomatic Individual'`). The `includesFamily` flag for `checkomatic_individual` must be set to `true` so the JS shows Screen 2 — do this locally in the return array, not in `MembershipTypes.php` (which would affect other flows).

Two existing tests break with this change:
- `test_available_levels_excludes_current_type` asserts `assertCount(5, $levels)` — becomes 3 (Individual excluded as current; `checkomatic_individual`, `lifetime_family`, `lifetime_individual` remain).
- `test_available_levels_flag_family_and_checkomatic` references `$byType['family']` which no longer exists.

- [ ] **Step 1: Run the existing test suite to establish baseline**

```bash
php artisan test tests/Unit/LevelChangeServiceTest.php --no-coverage
```

Expected: All 8 tests pass.

- [ ] **Step 2: Write the two new/updated tests (they will fail)**

Replace the two tests in `tests/Unit/LevelChangeServiceTest.php`:

```php
public function test_available_levels_returns_allowlisted_types_only(): void
{
    $svc = app(LevelChangeService::class);
    $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

    $types = array_column($levels, 'type');
    // individual is the current level — excluded
    $this->assertNotContains('individual', $types, 'current type excluded');
    // these were previously offered but are now removed
    $this->assertNotContains('family', $types, 'family no longer a change target');
    $this->assertNotContains('flat', $types, 'flat never a change target');
    $this->assertNotContains('checkomatic_family', $types, 'checkomatic_family never a change target');
    // the 3 remaining allowed slugs
    $this->assertContains('checkomatic_individual', $types);
    $this->assertContains('lifetime_family', $types);
    $this->assertContains('lifetime_individual', $types);
    $this->assertCount(3, $levels);
}

public function test_available_levels_checkomatic_label_and_flags(): void
{
    $svc = app(LevelChangeService::class);
    $levels = $svc->availableLevels($this->profileWithLevel('Individual'));

    $byType = array_column($levels, null, 'type');

    // Checkomatic label must be 'Checkomatic' (not 'Checkomatic Individual')
    $this->assertSame('Checkomatic', $byType['checkomatic_individual']['label']);
    // includesFamily must be true so the JS shows the optional spouse screen
    $this->assertTrue($byType['checkomatic_individual']['includesFamily']);
    $this->assertTrue($byType['checkomatic_individual']['isCheckomatic']);
    $this->assertArrayHasKey('fee', $byType['checkomatic_individual']);
    // Lifetime individual has no family
    $this->assertFalse($byType['lifetime_individual']['includesFamily']);
}

public function test_available_levels_excludes_current_when_checkomatic(): void
{
    $svc = app(LevelChangeService::class);
    // Member is already on checkomatic_individual — it should not appear
    $levels = $svc->availableLevels(
        $this->profileWithLevel('Checkomatic')
    );
    $types = array_column($levels, 'type');
    $this->assertNotContains('checkomatic_individual', $types);
    $this->assertCount(3, $levels); // individual, lifetime_family, lifetime_individual
}
```

- [ ] **Step 3: Run to confirm they fail**

```bash
php artisan test tests/Unit/LevelChangeServiceTest.php --no-coverage
```

Expected: `test_available_levels_returns_allowlisted_types_only`, `test_available_levels_checkomatic_label_and_flags`, and `test_available_levels_excludes_current_when_checkomatic` FAIL. The original two tests (`test_available_levels_excludes_current_type`, `test_available_levels_flag_family_and_checkomatic`) also fail because we haven't deleted them yet — delete them now as they are superseded.

- [ ] **Step 4: Update `availableLevels()` in `LevelChangeService.php`**

Replace lines 58–80 (the `availableLevels` method body):

```php
/**
 * The membership types the member may switch to — restricted to the four
 * supported change-level targets. The member's current level is excluded.
 * checkomatic_individual is labelled 'Checkomatic' and has includesFamily=true
 * so the modal shows the optional spouse screen (type submitted stays individual).
 *
 * @return array<int, array{type:string,label:string,fee:array,includesFamily:bool,isCheckomatic:bool}>
 */
public function availableLevels(MemberProfile $profile): array
{
    $currentSlug = MembershipTypes::slugFromLevelName($profile->level);

    $allowed = [
        'checkomatic_individual',
        'individual',
        'lifetime_family',
        'lifetime_individual',
    ];

    $levels = [];
    foreach ($allowed as $slug) {
        if ($slug === $currentSlug) {
            continue;
        }
        $isCheckomatic = MembershipTypes::isCheckomatic($slug);
        $levels[] = [
            'type'           => $slug,
            // Override label for checkomatic_individual — show 'Checkomatic'
            // not 'Checkomatic Individual' so the UI shows a single clean entry.
            'label'          => $slug === 'checkomatic_individual'
                                    ? 'Checkomatic'
                                    : MembershipTypes::labelForSlug($slug),
            'fee'            => $this->resolveFee($slug, 0, $isCheckomatic ? null : 0.0),
            // Force includesFamily=true for checkomatic_individual so the modal
            // shows the optional spouse screen. The submitted type never changes.
            'includesFamily' => $slug === 'checkomatic_individual'
                                    ? true
                                    : MembershipTypes::includesFamily($slug),
            'isCheckomatic'  => $isCheckomatic,
        ];
    }

    return $levels;
}
```

- [ ] **Step 5: Run tests — all should pass**

```bash
php artisan test tests/Unit/LevelChangeServiceTest.php --no-coverage
```

Expected: All tests pass (the 3 new ones + the 6 unchanged ones).

- [ ] **Step 6: Commit**

```bash
git add app/Services/LevelChangeService.php tests/Unit/LevelChangeServiceTest.php
git commit -m "feat: restrict change-level allowlist to 4 targets; label Checkomatic as spouse-eligible"
```

---

## Task 2: Reject `checkomatic_family` server-side in `charge()`

**Files:**
- Modify: `app/Services/LevelChangeService.php:99-106` (the `flat` reject block)
- Modify: `tests/Unit/LevelChangeServiceTest.php` (add one test)

### Context

`charge()` currently only rejects `flat`. Since `checkomatic_family` is no longer a valid target, a crafted POST could still reach the service. Add it to the reject block alongside `flat`.

- [ ] **Step 1: Write the failing test**

Add to `LevelChangeServiceTest`:

```php
public function test_charge_rejects_checkomatic_family(): void
{
    Queue::fake();
    $stripe = Mockery::mock(\App\Services\StripeService::class);
    $this->app->instance(\App\Services\StripeService::class, $stripe);

    $svc = app(LevelChangeService::class);
    $profile = new MemberProfile(['contact' => [
        'Id' => 999, 'Email' => 'a@b.com',
        'MembershipLevel' => ['Id' => 1, 'Name' => 'Individual'], 'FieldValues' => [],
    ]]);

    $result = $svc->charge(999, $profile, 'checkomatic_family', [], 'pm_test', 25.0);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('not available', $result['message']);
    $this->assertDatabaseCount('level_changes', 0);
    Queue::assertNotPushed(ProcessLevelChange::class);
}
```

- [ ] **Step 2: Run to confirm it fails**

```bash
php artisan test tests/Unit/LevelChangeServiceTest.php::test_charge_rejects_checkomatic_family --no-coverage
```

Expected: FAIL — `checkomatic_family` is not currently rejected, so the test fails because no error is returned.

- [ ] **Step 3: Update the reject block in `charge()`**

Replace the existing flat-only reject (around line 101–103):

```php
// Family Checkomatic and Flat Membership are not selectable level-change
// targets — reject server-side in case a crafted request bypasses the UI.
if ($toType === 'flat' || $toType === 'checkomatic_family') {
    return ['success' => false, 'message' => 'That membership type is not available as a level-change option.'];
}
```

- [ ] **Step 4: Run all LevelChangeService tests**

```bash
php artisan test tests/Unit/LevelChangeServiceTest.php --no-coverage
```

Expected: All tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Services/LevelChangeService.php tests/Unit/LevelChangeServiceTest.php
git commit -m "feat: reject checkomatic_family in charge() server-side"
```

---

## Task 3: Update the amount field and add the warning block (Screen 1)

**Files:**
- Modify: `resources/views/member-portal/partials/level-modal.blade.php`

### Context

Screen 1 currently has an amber box (`#lvlAmountBox`) with `min="20"` and no live feedback note. Changes:
1. Change `min` from 20 → 10.
2. Add a `<p id="lvlAmountNote">` feedback line below the input that reads "You will be charged $X.XX/month".
3. Add a `.lvl-checkomatic-warning` block below the amount box, visible whenever Checkomatic is selected.
4. Update the JS validation: `amt < 20` → `amt < 10`; add live note update function.

Do **not** change any other part of the HTML or JS yet.

- [ ] **Step 1: Update the `#lvlAmountBox` HTML**

Locate the existing amber box in the blade file (around the `lvlAmountBox` div). Replace it:

```html
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
```

- [ ] **Step 2: Add CSS for the new elements inside the `<style>` block**

Locate the existing `<style>` block at the top of the partial. Add after the existing `.lvl-amount-hint` rule:

```css
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
```

- [ ] **Step 3: Update the JS — add `updateLvlAmountNote()` and wire it**

In the existing IIFE, find where `monthlyInput` is declared and add the following function + wire it. Also update the `onLevelSelected` function to show/hide `lvlCheckomaticWarning`, and update the minimum validation from 20 → 10:

**Add these new variable references** near the top of the IIFE (after existing element lookups):

```js
const amountNote    = document.getElementById('lvlAmountNote');
const amountError   = document.getElementById('lvlAmountError');
const checkWarning  = document.getElementById('lvlCheckomaticWarning');
```

**Add this function** after the existing `hideError`/`showError` helpers:

```js
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
    // Re-enable Continue only when a level is selected
    pickNext.disabled = !_selected;
  }
}
```

**Wire the input event** — after the existing `monthlyInput` is referenced, add:

```js
monthlyInput?.addEventListener('input', updateLvlAmountNote);
```

**Update `onLevelSelected()`** — replace the Checkomatic show/hide block:

```js
if (_isCheckomatic) {
  amountBox.style.display = 'flex';
  if (checkWarning) checkWarning.style.display = '';
  updateLvlAmountNote();
} else {
  amountBox.style.display = 'none';
  if (checkWarning) checkWarning.style.display = 'none';
  if (monthlyInput) monthlyInput.value = '';
}
```

**Update the `pickNext` click handler** — change `amt < 20` → `amt < 10` and message:

```js
if (_selected.isCheckomatic) {
  const amt = parseFloat(monthlyInput.value);
  if (!amt || amt < 10) {
    showError(pickError, 'Please enter an amount of at least $10.00.');
    return;
  }
}
```

**Update `openModal()`** — add reset for the warning:

```js
if (checkWarning) checkWarning.style.display = 'none';
```

(Add this line right after `amountBox.style.display = 'none';` in `openModal()`.)

- [ ] **Step 4: Also update the pay-button re-validation** — find the `payBtn` click handler where it re-validates the monthly amount. Change `amt < 20` → `amt < 10`:

```js
if (!monthlyAmount || monthlyAmount < 10) {
  showError(payError, 'Please enter an amount of at least $10.00 on the first step.');
  return;
}
```

- [ ] **Step 5: Clear view cache and verify no syntax errors**

```bash
php artisan view:clear
```

Expected: `INFO  Compiled views cleared successfully.`

- [ ] **Step 6: Commit**

```bash
git add resources/views/member-portal/partials/level-modal.blade.php
git commit -m "feat: update checkomatic amount field (min $10, live note) and add recurring-billing warning"
```

---

## Task 4: Add spouse-disclaimer modal and Checkomatic Screen 2 path

**Files:**
- Modify: `resources/views/member-portal/partials/level-modal.blade.php`

### Context

Currently Screen 2 is shown only when `_selected.includesFamily` is true. After Task 1, `checkomatic_individual` has `includesFamily: true` in the API response, so the JS will already route into Screen 2 for Checkomatic. Two things still need to happen:

1. **Screen 2 heading/sub-text** — when the selected level is Checkomatic, the heading should read "Add Spouse (Optional)" and the sub-text should read "You may add your spouse to your membership. Adding a spouse does not change your monthly rate." instead of the generic family text.

2. **Spouse disclaimer modal** — a new `.confirm-overlay` overlay that fires when the "Add Spouse" (i.e. `#lvlAddMember`) button is clicked while the selected type is Checkomatic. The user must click "Got It" before the spouse block appears.

3. **Hide "+ Add Member" button for Checkomatic** — keep it for lifetime_family (which allows multiple family members), but for Checkomatic the button should be hidden after the first click (spouse-only).

- [ ] **Step 1: Add the disclaimer modal HTML**

Add immediately before the closing `</div>` of the main `.renew-overlay` div (i.e. just before `</div>` that closes `<div class="renew-modal">`):

```html
{{-- Checkomatic spouse disclaimer — shown before the spouse form is revealed --}}
<div class="confirm-overlay" id="lvlSpouseDisclaimer" aria-hidden="true">
  <div class="confirm-box">
    <div class="confirm-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 8v4"/><path d="M12 16h.01"/>
        <path d="M10.29 3.86l-8.43 14.5A2 2 0 0 0 3.58 21h16.84a2 2 0 0 0 1.72-2.64l-8.43-14.5a2 2 0 0 0-3.44 0z"/>
      </svg>
    </div>
    <p class="confirm-title">Checkomatic Reminder</p>
    <p class="confirm-body" style="margin-bottom:0.9rem;">
      Before adding a spouse, please review the payment reminder below.
    </p>
    <div class="lvl-spouse-disclaimer-note">
      Checkomatic dues totaling $20 per member must be paid by June 30th, {{ date('Y') }}.
    </div>
    <div class="confirm-actions" style="margin-top:1.25rem;">
      <button type="button" class="confirm-btn-yes" id="lvlSpouseDisclaimerOk">Got It</button>
    </div>
  </div>
</div>
```

- [ ] **Step 2: Add CSS for the disclaimer note**

In the existing `<style>` block, add:

```css
.lvl-spouse-disclaimer-note {
  background: #fffbeb;
  border: 1px solid #fcd34d;
  border-radius: 8px;
  padding: 10px 13px;
  font-size: 13px;
  color: #92400e;
  font-weight: 600;
  line-height: 1.5;
}
```

- [ ] **Step 3: Wire the disclaimer modal + update the Add Member button for Checkomatic**

Add these new variable references near the top of the IIFE (after existing lookups):

```js
const spouseDisclaimer    = document.getElementById('lvlSpouseDisclaimer');
const spouseDisclaimerOk  = document.getElementById('lvlSpouseDisclaimerOk');
let   _spouseDisclaimerShown = false;
```

Replace the existing `addMemberBtn` click listener with:

```js
addMemberBtn?.addEventListener('click', () => {
  if (_isCheckomatic && !_spouseDisclaimerShown) {
    // Show disclaimer first; on "Got It" we add the block
    if (spouseDisclaimer) {
      spouseDisclaimer.classList.add('open');
      spouseDisclaimer.setAttribute('aria-hidden', 'false');
    }
    return;
  }
  addFamilyBlock(_isCheckomatic); // fixed=true for checkomatic (no remove btn)
  if (_isCheckomatic) addMemberBtn.style.display = 'none'; // one spouse only
});
```

Add the "Got It" handler right after:

```js
spouseDisclaimerOk?.addEventListener('click', () => {
  _spouseDisclaimerShown = true;
  if (spouseDisclaimer) {
    spouseDisclaimer.classList.remove('open');
    spouseDisclaimer.setAttribute('aria-hidden', 'true');
  }
  addFamilyBlock(true); // fixed block, no remove btn
  addMemberBtn.style.display = 'none'; // one spouse only
});
```

- [ ] **Step 5: Reset disclaimer state in `openModal()`**

Inside `openModal()`, add:

```js
_spouseDisclaimerShown = false;
```

(Add after the existing `_isCheckomatic = false;` line.)

Also make the `buildFamilyScreen()` function expose `addMemberBtn` for Checkomatic (it starts hidden from `buildFamilyScreen`; we set it back to visible when entering the screen so the member can click it):

Update the start of `buildFamilyScreen()` to always reset the button label for Checkomatic:

```js
function buildFamilyScreen() {
  familyBox.innerHTML = '';
  _spouseDisclaimerShown = false; // reset on each entry to family screen
  if (_isCheckomatic) {
    familyHeading.textContent = 'Add Spouse (Optional)';
    familySub.textContent = 'You may add your spouse to your membership. Adding a spouse does not change your monthly rate.';
    addMemberBtn.textContent = '+ Add Spouse';
    addMemberBtn.style.display = ''; // visible — clicking it triggers disclaimer
  } else if (isSpouseOnly()) {
    familyHeading.textContent = 'Add Spouse';
    familySub.textContent = 'This level includes your spouse. Enter their details below.';
    addMemberBtn.style.display = 'none';
    addFamilyBlock(true);
  } else {
    familyHeading.textContent = 'Add Family Members';
    familySub.textContent = 'This level includes family members. Add the people you would like covered under your membership.';
    addMemberBtn.textContent = '+ Add Member';
    addMemberBtn.style.display = '';
    addFamilyBlock(false);
  }
}
```

- [ ] **Step 6: Clear view cache**

```bash
php artisan view:clear
```

Expected: `INFO  Compiled views cleared successfully.`

- [ ] **Step 7: Commit**

```bash
git add resources/views/member-portal/partials/level-modal.blade.php
git commit -m "feat: add checkomatic spouse-disclaimer modal and optional spouse screen (Screen 2)"
```

---

## Task 5: Run full test suite and verify

**Files:** None (read-only verification)

- [ ] **Step 1: Run all unit tests**

```bash
php artisan test tests/Unit --no-coverage
```

Expected: All tests pass, no failures.

- [ ] **Step 2: Run feature tests**

```bash
php artisan test tests/Feature --no-coverage
```

Expected: All tests pass. If `MembershipWildApricotTest` fails, check whether it is an environmental failure (WA API key / network) rather than a code regression — that test class is known to have environment-dependent failures (see project memory).

- [ ] **Step 3: Smoke-check the change-level modal manually**

Start the app (`php artisan serve` or XAMPP). Log in as a member with any non-Checkomatic level.

Check these flows:

| Scenario | Expected |
|----------|----------|
| Open modal | Dropdown shows at most 4 options; current level absent |
| Select Individual / Lifetime Family / Lifetime Individual | Continue enabled; no amount box; no warning |
| Select Checkomatic | Amber amount box appears; warning block appears; note reads "You will be charged $10.00/month"; Continue disabled until amount ≥ 10 |
| Enter 5 in amount field | Note hidden; error shown; Continue stays disabled |
| Enter 15 in amount field | Note reads "You will be charged $15.00/month"; error hidden; Continue enabled |
| Click Continue on Checkomatic | Screen 2 shows with heading "Add Spouse (Optional)" and "+ Add Spouse" button; no pre-filled block |
| Click "+ Add Spouse" | Disclaimer modal appears |
| Click "Got It" | Disclaimer closes; spouse block appears; "+ Add Spouse" button hides |
| Click "Continue" on Screen 2 (no spouse) | Goes to Review with correct amount |
| Click "Continue" on Screen 2 (with spouse) | Family validation runs then goes to Review |

- [ ] **Step 4: Final commit (if any last-minute fixes were made)**

```bash
git add -p
git commit -m "fix: address smoke-test findings in level-change checkomatic flow"
```

(Skip this step if no fixes were needed.)
