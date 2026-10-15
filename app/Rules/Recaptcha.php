<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    /** Asks Google whether the "I'm not a robot" token is real. */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('recaptcha.enabled')) {
            return;
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => config('recaptcha.secret'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            if (! $response->json('success')) {
                $fail('reCAPTCHA verification failed. Please try again.');
            }
        } catch (\Throwable $e) {
            $fail('Could not reach reCAPTCHA service. Please try again.');
        }
    }
}