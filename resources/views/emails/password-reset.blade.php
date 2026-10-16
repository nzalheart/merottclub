@extends('emails.layout')

@section('body')
    <p style="margin:0 0 12px;">Hello {{ $user->name }},</p>
    <p style="margin:0 0 16px;">We received a request to reset the password for your <strong>{{ $appName }}</strong> account.</p>
    <p style="margin:0 0 16px;">
        <a href="{{ $resetUrl }}" style="display:inline-block;background:#003893;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:10px;">Reset password</a>
    </p>
    <p style="margin:0;">This link expires in {{ config('auth.passwords.users.expire') }} minutes. If you did not ask for a reset, you can ignore this email.</p>
@endsection
