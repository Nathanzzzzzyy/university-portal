<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class VerificationController extends Controller
{
    public function notice(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard') : view('auth.verify-email', ['resendSeconds' => $this->resendSeconds($request)]);
    }

    public function verify(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
        if ($request->has('otp_digits')) {
            $request->validate([
                'otp_digits' => ['required', 'array', 'size:4'],
                'otp_digits.*' => ['required', 'string', 'regex:/^[0-9]$/D'],
            ], ['otp_digits.*.required' => 'Please enter all four digits.', 'otp_digits.*.regex' => 'Each box must contain one number.']);
            $digits = $request->input('otp_digits');
            if (array_keys($digits) !== [0, 1, 2, 3]) {
                throw ValidationException::withMessages(['otp' => 'Please enter all four digits in order.']);
            }
            $request->merge(['otp' => implode('', $digits)]);
        }
        $request->validate(['otp' => ['required', 'string', 'regex:/^[0-9]{4}$/D']], [
            'otp.required' => 'Enter the four-digit code from your Gmail.',
            'otp.regex' => 'Your verification code must contain exactly four numbers.',
        ]);
        $key = 'email-otp:'.$request->user()->id;
        $attempts = $key.':attempts';
        if (RateLimiter::tooManyAttempts($attempts, 5)) {
            throw ValidationException::withMessages(['otp' => 'Too many attempts. Try again in '.RateLimiter::availableIn($attempts).' seconds.']);
        }
        RateLimiter::hit($attempts, 600);
        $otp = Cache::get($key);
        if (! $otp || $otp['expires'] <= now()->timestamp || $otp['email'] !== $request->user()->email || ! Hash::check($request->otp, $otp['hash'])) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired OTP. Check your email or request a new code.']);
        }
        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }
        Cache::forget($key);
        RateLimiter::clear($attempts);
        $request->session()->forget('url.intended');
        return redirect()->route('dashboard')->with('status', 'Email verified. Welcome to UC Portal!');
    }

    private function resendSeconds(Request $request): int
    {
        $expires = Cache::get('email-otp:'.$request->user()->id.':cooldown');
        return is_int($expires) ? max(0, $expires - now()->timestamp) : ($expires ? 60 : 0);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['otp' => 'Email could not be sent. Please try again shortly.']);
        }
        return back()->with('status', 'A new OTP has been sent to your Gmail. It expires in 10 minutes.');
    }
}
