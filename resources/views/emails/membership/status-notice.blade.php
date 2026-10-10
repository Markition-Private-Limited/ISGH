@extends('emails.membership.layout')
@section('body')
@php $contact = \App\Support\MembershipVerification::contact(); $d = $details; @endphp
<p>Assalamu Alaikum {{ $d['name'] }},</p>

@if ($scenario === \App\Support\MembershipVerification::ACTIVE_ELIGIBLE)
  <p>Thank you for verifying your voting status with the Islamic Society of Greater Houston. As of now, you are an active member and are eligible to opt in for online voting in the upcoming ISGH elections.</p>
  <p style="font-weight:700;">Information on file</p>
  @include('emails.membership._details', ['rows' => [
    'Member ID' => $d['member_id'],
    'Name' => $d['name'],
    'Membership type' => $d['type'],
    'Membership status' => 'Active',
    'Expiry / renewal date' => $d['expiry'],
    'Assigned center' => $d['center'],
    'Voting eligibility' => 'Eligible',
    'Online opt-in' => $d['opt_in'],
  ]])
  <p style="font-weight:700;">To opt in for online voting</p>
  <ol style="padding-left:20px;">
    <li>Return to the verification page: <a href="{{ $verificationLink }}" style="color:#0a5e3a;">{{ $verificationLink }}</a></li>
    <li>Enter your email address.</li>
    <li>Upload a copy of your Texas Driver License or ID.</li>
    <li>Accept the Identity Verification Policy and submit.</li>
  </ol>
  <p>You will not be opted in for online voting until the Membership Department verifies your identity. Once you submit your information, you will receive a confirmation email within {{ config('membership.verification.review_timeline') }}.</p>
  <p>If any of the information above is incorrect, please contact the Membership Department at <a href="mailto:{{ $contact['email'] }}" style="color:#0a5e3a;">{{ $contact['email'] }}</a>.</p>

@elseif ($scenario === \App\Support\MembershipVerification::ACTIVE_LATE)
  <p>Thank you for verifying your membership with the Islamic Society of Greater Houston. Our records show that your membership is active, and the details on file are shown below.</p>
  <p style="font-weight:700;">Information on file</p>
  @include('emails.membership._details', ['rows' => [
    'Member ID' => $d['member_id'],
    'Name' => $d['name'],
    'Membership type' => $d['type'],
    'Membership status' => 'Active',
    'Registration date' => $d['registration'],
    'Assigned center' => $d['center'],
    'Voting eligibility (2026)' => 'Not eligible',
  ]])
  <p>Eligibility to vote in the 2026 ISGH elections required membership registration on or before June 30, 2026. Our records indicate that your membership was registered after this date. Accordingly, you are not eligible to vote in the upcoming ISGH elections.</p>
  <p>If any of the information above is incorrect, please contact the Membership Department at <a href="mailto:{{ $contact['email'] }}" style="color:#0a5e3a;">{{ $contact['email'] }}</a> and include your Member ID.</p>

@else
  <p>Thank you for verifying your membership with the Islamic Society of Greater Houston. We have reviewed your record, and the details on file are shown below.</p>
  <p style="font-weight:700;">Information on file</p>
  @include('emails.membership._details', ['rows' => [
    'Member ID' => $d['member_id'],
    'Name' => $d['name'],
    'Membership type' => $d['type'],
    'Membership status' => 'Lapsed',
    'Expiry / renewal date' => $d['expiry'],
    'Assigned center' => $d['center'],
    'Voting eligibility (2026)' => 'Not eligible',
  ]])
  <p>Eligibility to vote in the 2026 ISGH elections required an active, renewed membership on or before June 30, 2026. Our records show your membership had lapsed as of that date. Renewing your membership now will return it to active status and will not change voting eligibility for the 2026 elections.</p>
  <p>You may renew at any time here: <a href="{{ $renewalLink }}" style="color:#0a5e3a;">{{ $renewalLink }}</a></p>
  <p>If any of the information above is incorrect, or if you renewed on or before June 30, 2026, please contact the Membership Department at <a href="mailto:{{ $contact['email'] }}" style="color:#0a5e3a;">{{ $contact['email'] }}</a> so we may review your record.</p>
@endif

@include('emails.membership._signature')
@endsection
