@extends('emails.membership.layout')
@section('body')
@php $contact = \App\Support\MembershipVerification::contact(); @endphp
<p>Assalamu Alaikum {{ $firstName }} {{ $lastName }},</p>
<p>We have received your membership inquiry. Our records did not return a match for the information entered, and our Membership Department is reviewing your submission. You will receive a follow-up email within {{ config('membership.verification.review_timeline') }}.</p>
<p style="font-weight:700;">Information received</p>
@include('emails.membership._details', ['rows' => [
  'First name' => $firstName,
  'Last name' => $lastName,
  'Numerical street address' => $streetNumber,
  'Date of birth' => $dob,
  'Email' => $email,
  'Submitted' => $submittedAt,
]])
<p>If any of the information above was entered incorrectly, please contact the Membership Department at <a href="mailto:{{ $contact['email'] }}" style="color:#0a5e3a;">{{ $contact['email'] }}</a>.</p>
@include('emails.membership._signature')
@endsection
