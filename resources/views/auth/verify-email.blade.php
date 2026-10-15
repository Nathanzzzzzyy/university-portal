@extends('layouts.guest')
@section('title', 'Verify your Gmail')
@section('brand')
    <p class="tag">A small step. A fresh start.</p>
@endsection
@section('content')
<div class="otp-screen">
    <div class="otp-step"><span>01 Account created</span><span aria-hidden="true">&mdash;</span><b>02 Verify email</b></div>
    <div class="otp-emblem" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 7 9 6 9-6"/></svg><span>&check;</span></div>
    <span class="otp-eyebrow">ONE LAST STEP</span>
    <h2>Check your inbox.</h2>
    <p class="otp-description">Enter the <b>4-digit code</b> sent to your Gmail to open your {{ auth()->user()->isTeacher() ? 'teaching' : 'student' }} workspace.</p>
    <div class="otp-email">{{ auth()->user()->email }}</div>
    <form method="POST" action="{{ route('verification.verify') }}" id="otp-form">
        @csrf
        <fieldset class="otp-fieldset" aria-describedby="otp-help otp-error"><legend>Verification code</legend><div class="otp-boxes">
        @for($i = 0; $i < 4; $i++)
            <input class="otp-digit" name="otp_digits[{{ $i }}]" type="text" inputmode="numeric" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" pattern="[0-9]" maxlength="1" aria-label="Digit {{ $i + 1 }} of 4" aria-invalid="{{ $errors->any() ? 'true' : 'false' }}" required @if($i === 0) autofocus @endif>
        @endfor
        </div></fieldset>
        <p id="otp-help" class="otp-help">Code expires in 10 minutes. You can paste all four digits.</p>
        <div id="otp-error" class="otp-error" role="alert">@if($errors->any()){{ $errors->first() }}@endif</div>
        <button class="btn block otp-submit" type="submit">Verify &amp; continue <span aria-hidden="true">&rarr;</span></button>
    </form>
    <div class="otp-resend"><p>Didn&rsquo;t receive a code?</p><form method="POST" action="{{ route('verification.send') }}">@csrf<button id="otp-resend" class="otp-resend-button" type="submit" data-seconds="{{ $resendSeconds }}">Resend code</button></form><span id="resend-status" class="otp-help" aria-live="polite"></span></div>
    <div class="otp-tip"><span aria-hidden="true">&#9432;</span><p>Check Spam or Promotions in Gmail. Requesting a new code replaces the previous one.</p></div>
    <form method="POST" action="{{ route('logout') }}" class="otp-logout">@csrf<button type="submit">Use a different account</button></form>
</div>
<script src="{{ asset('js/otp.js') }}" defer></script>
@endsection
