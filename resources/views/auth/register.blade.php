@extends('layouts.guest')
@section('title', 'Create Account')

@section('brand')
    <p class="tag">Join the UC Community</p>
    <p style="text-transform:none;letter-spacing:0;color:#e9f3ec;margin-top:8px">Create your account to access your academic journey.</p>
@endsection

@section('content')
    <h2>Create Account</h2>
    <p class="sub">Create a student or teacher account. We'll send an OTP to your personal Gmail.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="field">
            <label for="auth-name">Full Name</label>
            <input id="auth-name" type="text" name="name" minlength="2" maxlength="255" autocomplete="name" value="{{ old('name') }}" placeholder="Enter your full name" required autofocus>
            @error('name') <div class="err">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="auth-email">Email Address</label>
            <input id="auth-email" type="email" name="email" maxlength="255" pattern="[^@\s]+@gmail\.com" title="Use your personal @gmail.com address" value="{{ old('email') }}" placeholder="yourname@gmail.com" autocomplete="email" required>
            @error('email') <div class="err">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="role">Account type</label>
            <select id="role" name="role" required>
                <option value="student" @selected(old('role', request('role', 'student')) === 'student')>Student</option>
                <option value="teacher" @selected(old('role', request('role')) === 'teacher')>Teacher</option>
            </select>
            @error('role') <div class="err">{{ $message }}</div> @enderror
        </div>
        <div class="row" id="student-fields">
            <div class="field">
                <label>Student Number</label>
                <input type="text" value="Auto-generated" disabled style="background:#f1f5f2;color:#6b7a72">
            </div>
            <div class="field">
                <label for="auth-course">Course</label>
            <select id="auth-course" name="course" required>
                    <option value="">Select...</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c }}" @selected(old('course') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
                @error('course') <div class="err">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="field">
                <label for="auth-password">Password</label>
            <input id="auth-password" type="password" name="password" minlength="8" maxlength="72" placeholder="Min. 8 chars, letters + numbers" required>
                @error('password') <div class="err">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="auth-password_confirmation">Confirm Password</label>
            <input id="auth-password_confirmation" type="password" name="password_confirmation" minlength="8" maxlength="72" placeholder="Confirm password" required>
            </div>
        </div>

        @if (config('recaptcha.enabled'))
            <div class="captcha"><div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div></div>
            @error('g-recaptcha-response') <div class="err" style="text-align:center">{{ $message }}</div> @enderror
        @endif

        <p class="sub">Use at least 8 characters with letters and numbers. Verify your email before accessing your dashboard.</p>
        <button class="btn block" type="submit">Create account &amp; send OTP</button>
    </form>

    <script>
        const role = document.getElementById('role');
        const studentFields = document.getElementById('student-fields');
        function updateRole() {
            studentFields.hidden = role.value === 'teacher';
            studentFields.querySelector('select').required = role.value === 'student';
            studentFields.querySelector('select').disabled = role.value === 'teacher';
        }
        role.addEventListener('change', updateRole); updateRole();
        const password = document.querySelector('[name="password"]');
        const confirmation = document.querySelector('[name="password_confirmation"]');
        function checkPasswordMatch() {
            confirmation.setCustomValidity(confirmation.value && confirmation.value !== password.value ? 'Your passwords do not match.' : '');
        }
        password.addEventListener('input', checkPasswordMatch);
        confirmation.addEventListener('input', checkPasswordMatch);
    </script>
    <p class="foot">Already have an account? <a href="{{ route('login') }}"><b>Login here</b></a></p>
@endsection