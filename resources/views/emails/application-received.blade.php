@extends('emails.layout')

@section('body')
    <p style="margin:0 0 12px;">Hello {{ $applicant->name }},</p>
    <p style="margin:0 0 12px;">We received your application to join <strong>{{ $appName }}</strong>.</p>
    <p style="margin:0;">An admin still needs to approve your account. You will get another email when it is accepted, and you can sign in after that.</p>
@endsection
