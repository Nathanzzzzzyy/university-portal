<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public const COURSES = ['BSIT', 'BSCS', 'BSBA', 'BSHM', 'BEED', 'BSED', 'BSN', 'BSCE'];

    public function show()
    {
        return view('auth.register', ['courses' => self::COURSES]);
    }

    public function store(Request $request)
    {
        if (is_string($request->email)) { $request->merge(['email' => strtolower(trim($request->email))]); }
        if (is_string($request->name)) { $request->merge(['name' => trim($request->name)]); }
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'regex:/^[^@\s]+@gmail\.com$/i', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:student,teacher'],
            'course' => ['required_if:role,student', 'nullable', 'in:'.implode(',', self::COURSES)],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(8)->letters()->numbers()],
            'g-recaptcha-response' => config('recaptcha.enabled') ? ['required', new Recaptcha] : [],
        ], [
            'email.regex' => 'Please use a personal @gmail.com address.',
            'email.unique' => 'This Gmail address is already registered. Please log in.',
            'password.confirmed' => 'Your passwords do not match.',
            'course.required_if' => 'Please select your course for a student account.',
            'g-recaptcha-response.required' => 'Please tick "I\'m not a robot".',
        ]);

                $attributes = [
            'name' => $data['name'], 'email' => $data['email'],
            'password' => $data['password'], 'status' => 'active',
        ];
        $user = $data['role'] === 'teacher'
            ? User::create($attributes + ['role' => 'teacher'])
            : User::createStudent($attributes + ['course' => $data['course'], 'year_level' => '1st Year']);

        Auth::login($user);
        $request->session()->regenerate();
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('verification.notice')->withErrors(['otp' => 'Account saved, but email could not be sent. Please try Resend OTP shortly.']);
        }

        return redirect()->route('verification.notice')
            ->with('status', 'Account created! '.($user->student_number ? 'Your Student Number is '.$user->student_number.'. ' : '').'Enter the OTP sent to your Gmail.');
    }
}