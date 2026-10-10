@extends('emails.membership.layout')
@section('body')
@php $contact = \App\Support\MembershipVerification::contact(); @endphp
<p>Assalamu Alaikum {{ $name }},</p>
<p>We have received your request to verify your membership status. Our Membership Department is currently reviewing your record and will contact you within {{ config('membership.verification.review_timeline') }}.</p>
@if ($scenario === \App\Support\MembershipVerification::LAPSED)
  <p>If you need to provide additional information, such as proof of renewal on or before June 30, 2026, please reply to this email or contact <a href="mailto:{{ $contact['email'] }}" style="color:#0a5e3a;">{{ $contact['email'] }}</a>.</p>
@else
  <p>If you need to provide additional information, please reply to this email or contact <a href="mailto:{{ $contact['email'] }}" style="color:#0a5e3a;">{{ $contact['email'] }}</a>.</p>
@endif
@include('emails.membership._signature')
@endsection
