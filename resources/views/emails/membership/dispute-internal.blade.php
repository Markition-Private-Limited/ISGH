@extends('emails.membership.layout')
@section('body')
<p>Assalamu Alaikum Membership Team,</p>
@if ($scenario === \App\Support\MembershipVerification::LAPSED)
  <p>The member below received a lapsed result and believes it to be incorrect. Please review the record, confirm any renewal made on or before June 30, 2026, and respond directly.</p>
  @include('emails.membership._details', ['rows' => [
    'Name' => $d['name'],
    'Member ID' => $d['member_id'],
    'Email' => $d['email'],
    'Expiry / renewal date on file' => $d['expiry'],
    'Result shown' => 'Lapsed, not eligible to vote in 2026',
    "Member's message" => $memberMessage,
    'Submitted' => $submittedAt,
  ]])
@else
  <p>The member below received a result of Active, not eligible to vote (registered after June 30, 2026) and believes this to be incorrect. Please review the record and respond directly.</p>
  @include('emails.membership._details', ['rows' => [
    'Name' => $d['name'],
    'Member ID' => $d['member_id'],
    'Email' => $d['email'],
    'Registration date on file' => $d['registration'],
    'Result shown' => 'Active, not eligible (registered after June 30)',
    "Member's message" => $memberMessage,
    'Submitted' => $submittedAt,
  ]])
@endif
@include('emails.membership._signature_internal')
@endsection
