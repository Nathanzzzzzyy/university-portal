<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showStudent()
    {
        return view('auth.login');
    }

    public function showTeacher()
    {
        return view('auth.teacher-login');
    }

    public function student(Request $request)
    {
        return $this->attempt($request, 'student', 'login');
    }

    public function teacher(Request $request)
    {
        return $this->attempt($request, 'teacher', 'teacher.login');
    }

    private function attempt(Request $request, string $role, string $backRoute)
    {
        $field = $role === 'teacher' ? 'username' : 'login';

        if (is_string($request->input($field))) {
            $value = trim($request->input($field));
            $request->merge([$field => str_contains($value, '@') ? strtolower($value) : $value]);
        }
        $request->validate([
            $field => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:72'],
            'g-recaptcha-response' => config('recaptcha.enabled') ? ['required', new Recaptcha] : [],
        ], [
            'g-recaptcha-response.required' => 'Please tick "I\'m not a robot".',
        ]);

        // Students may log in with email OR student number; teachers with username OR email.
        $value = $request->input($field);
        $column = filter_var($value, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : ($role === 'teacher' ? 'username' : 'student_number');

        $credentials = [$column => $value, 'password' => $request->password, 'role' => $role, 'status' => 'active'];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return redirect()->route($backRoute)
                ->withInput($request->only($field))
                ->withErrors([$field => 'These credentials do not match our records (or the account is inactive).']);
        }

        $request->session()->regenerate();

        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended($role === 'teacher' ? route('teacher.dashboard') : route('student.dashboard'));
    }

    public function logout(Request $request)
    {
        $wasTeacher = $request->user()?->isTeacher();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($wasTeacher ? 'teacher.login' : 'login')->with('status', 'You have been logged out.');
    }
}