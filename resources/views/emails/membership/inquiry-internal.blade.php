@extends('emails.membership.layout')
@section('body')
<p>Assalamu Alaikum Membership Team,</p>
<p>The individual below searched for their membership record and no match was found. Please review the details and respond directly to the email provided.</p>
@include('emails.membership._details', ['rows' => [
  'First name' => $firstName,
  'Last name' => $lastName,
  'Numerical street address entered' => $streetNumber,
  'Date of birth entered' => $dob,
  'Contact email' => $email,
  'Submitted' => $submittedAt,
]])
@include('emails.membership._signature_internal')
@endsection
