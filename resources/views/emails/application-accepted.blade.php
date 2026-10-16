@extends('emails.layout')

@section('body')
    <p style="margin:0 0 12px;">Hello {{ $applicant->name }},</p>
    <p style="margin:0 0 12px;">Your application to <strong>{{ $appName }}</strong> has been accepted. You can sign in now.</p>
    <p style="margin:0;">
        <a href="{{ $portalUrl }}" style="display:inline-block;background:#003893;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:700;">Open the portal</a>
    </p>
@endsection
