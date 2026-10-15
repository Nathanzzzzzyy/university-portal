@extends('layouts.guest')
@section('title', 'Login')

@section('brand')
    <p class="tag">Building Lives, Shaping the Future</p>
@endsection

@section('content')
    <h2>Welcome Back!</h2>
    <p class="sub">Log in to your University Portal</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label for="auth-login">Email or Student Number</label>
            <input id="auth-login" type="text" name="login" maxlength="255" autocomplete="username" value="{{ old('login') }}" placeholder="example@gmail.com or 2026-0000" required autofocus>
            @error('login') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="auth-password">Password</label>
            <input id="auth-password" type="password" name="password" maxlength="72" placeholder="Password" required>
            @error('password') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="between">
            <label class="check"><input type="checkbox" name="remember"> Remember Me</label>
            <a href="#" onclick="alert('Please contact the Registrar or an teacher to reset your password.');return false">Forgot Password?</a>
        </div>

        @if (config('recaptcha.enabled'))
            <div class="captcha"><div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div></div>
            @error('g-recaptcha-response') <div class="err" style="text-align:center">{{ $message }}</div> @enderror
        @endif

        <button class="btn block" type="submit">Login</button>
    </form>

    <p class="foot">Don't have an account? <a href="{{ route('register') }}"><b>Register here</b></a><br>
        <a href="{{ route('teacher.login') }}" style="font-size:12px">Teacher login</a></p>
@endsection