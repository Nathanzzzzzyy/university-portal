@extends('layouts.guest')
@section('title', 'Teacher Login')

@section('brand')
    <p class="tag">Teacher Portal</p>
@endsection

@section('content')
    <h2>Teacher Login</h2>
    <p class="sub">Access the teacher panel</p>

    <form method="POST" action="{{ route('teacher.login') }}">
        @csrf
        <div class="field">
            <label for="auth-username">Username or Email</label>
            <input id="auth-username" type="text" name="username" maxlength="255" autocomplete="username" value="{{ old('username') }}" placeholder="example@gmail.com" required autofocus>
            @error('username') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="auth-password">Password</label>
            <input id="auth-password" type="password" name="password" maxlength="72" placeholder="Password" required>
            @error('password') <div class="err">{{ $message }}</div> @enderror
        </div>
        <label class="check" style="margin-bottom:14px"><input type="checkbox" name="remember"> Remember Me</label>

        @if (config('recaptcha.enabled'))
            <div class="captcha"><div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div></div>
            @error('g-recaptcha-response') <div class="err" style="text-align:center">{{ $message }}</div> @enderror
        @endif

        <button class="btn block" type="submit">Login</button>
    </form>
    <p class="foot">New teacher? <a href="{{ route('register', ['role' => 'teacher']) }}">Create an account</a><br><a href="{{ route('login') }}">Back to Portal</a></p>
@endsection