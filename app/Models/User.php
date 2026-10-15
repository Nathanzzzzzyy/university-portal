<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'status',
        'student_number', 'username', 'course', 'year_level',
        'birthdate', 'gender', 'address', 'contact_number', 'profile_photo_path', 'preferences',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthdate'         => 'date',
            'password'          => 'hashed',
            'preferences' => 'array',
        ];
    }

    public function sendEmailVerificationNotification()
    {
        $key = 'email-otp:'.$this->id;
        if (\Illuminate\Support\Facades\Cache::has($key.':cooldown')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['otp' => 'Please wait 60 seconds before requesting another OTP.']);
        }
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(10);
        \Illuminate\Support\Facades\Mail::raw(
            "Your UC Portal verification OTP is {$code}. It expires in 10 minutes. Do not share this code.",
            fn ($message) => $message->to($this->email)->subject('UC Portal verification OTP')
        );
        \Illuminate\Support\Facades\Cache::put($key, ['hash' => \Illuminate\Support\Facades\Hash::make($code), 'expires' => $expires->timestamp, 'email' => $this->email], $expires);
        \Illuminate\Support\Facades\Cache::put($key.':cooldown', now()->addSeconds(60)->timestamp, 60);
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Next sequential student number: <year>-<4 digits>, e.g. 2026-0001, 2026-0002 ...
     * The counter restarts every year.
     */
    public static function nextStudentNumber(): string
    {
        $prefix = now()->format('Y').'-';
        $last = static::where('student_number', 'like', $prefix.'%')->max('student_number');
        $next = $last ? ((int) substr($last, 5)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /** Create a student and give them the next number (retries if two people register at the same moment). */
    public static function createStudent(array $data): static
    {
        for ($try = 1; ; $try++) {
            try {
                return static::create($data + ['role' => 'student', 'student_number' => static::nextStudentNumber()]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                if ($try >= 5 || ! str_contains($e->getMessage(), 'student_number')) {
                    throw $e;
                }
            }
        }
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /** GPA = weighted average of graded subjects (lower is better, PH scale). */
    public function gpa(): ?float
    {
        $rows = $this->enrollments()->whereNotNull('grade')->with('subject')->get();
        $units = $rows->sum(fn ($e) => $e->subject->units);

        return $units ? round($rows->sum(fn ($e) => $e->grade * $e->subject->units) / $units, 2) : null;
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    }
}