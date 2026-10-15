<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['recaptcha.enabled' => false, 'mail.default' => 'array']);
    }

    public function test_student_signup_sends_otp_and_verifies_email(): void
    {
        $code = null;
        Event::listen(MessageSending::class, function ($event) use (&$code) {
            preg_match('/OTP is (\d{4})/', $event->message->getTextBody(), $match);
            $code = $match[1] ?? null;
        });
        $this->post('/register', ['name' => 'Student One', 'email' => 'student@gmail.com', 'role' => 'student', 'course' => 'BSIT', 'password' => 'Password123', 'password_confirmation' => 'Password123'])->assertRedirect(route('verification.notice'));
        $student = User::where('email', 'student@gmail.com')->firstOrFail();
        $this->assertNotNull($student->student_number);
        $this->assertNotNull($code);
        $this->get('/student')->assertRedirect(route('verification.notice'));
        $this->post('/email/verify', ['otp' => 'invalid'])->assertSessionHasErrors('otp');
        $this->post('/email/verify', ['otp' => $code])->assertRedirect(route('dashboard'));
        $this->assertTrue($student->fresh()->hasVerifiedEmail());
        $this->get('/teacher')->assertRedirect(route('teacher.login'));
    }

    public function test_teacher_signup_does_not_require_student_details(): void
    {
        $this->post('/register', ['name' => 'Teacher One', 'email' => 'teacher@gmail.com', 'role' => 'teacher', 'password' => 'Password123', 'password_confirmation' => 'Password123'])->assertRedirect(route('verification.notice'));
        $teacher = User::where('email', 'teacher@gmail.com')->firstOrFail();
        $this->assertSame('teacher', $teacher->role);
        $this->assertNull($teacher->student_number);
        $this->get('/teacher')->assertRedirect(route('verification.notice'));
        $teacher->markEmailAsVerified();
        $this->actingAs($teacher->fresh())->get('/teacher')->assertOk();
    }

    public function test_non_gmail_registration_is_rejected(): void
    {
        $this->post('/register', ['name' => 'Student', 'email' => 'student@uc.edu.ph', 'role' => 'student', 'course' => 'BSIT', 'password' => 'Password123', 'password_confirmation' => 'Password123'])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
    }
}
