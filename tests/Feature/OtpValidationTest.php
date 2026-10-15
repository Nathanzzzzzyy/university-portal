<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
class OtpValidationTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); config(['recaptcha.enabled'=>false,'mail.default'=>'array']); }
    private function pending(string $role='student'): User { return User::factory()->create(['role'=>$role,'email_verified_at'=>null]); }
    private function code(User $user, string $code='0007', int $minutes=10): void {
        Cache::put('email-otp:'.$user->id,['hash'=>Hash::make($code),'expires'=>now()->addMinutes($minutes)->timestamp,'email'=>$user->email],600);
    }
    public function test_four_boxes_verify_student_and_teacher_including_leading_zero(): void {
        foreach(['student','teacher'] as $role) {
            $user=$this->pending($role); $this->code($user);
            $this->actingAs($user)->get('/email/verify')->assertOk()->assertSee('otp_digits[0]',false)->assertSee('otp_digits[3]',false)->assertDontSee('otp_digits[4]',false);
            $this->post('/email/verify',['otp_digits'=>['0','0','0','7']])->assertRedirect(route('dashboard'));
            $this->assertTrue($user->fresh()->hasVerifiedEmail());
            $this->assertFalse(Cache::has('email-otp:'.$user->id));
            $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect(route($role.'.dashboard'));
        }
    }
    public function test_incomplete_nonnumeric_and_old_six_digit_codes_are_rejected(): void {
        $user=$this->pending(); $this->code($user); $this->actingAs($user);
        foreach(['123','123456','abcd','1e03'] as $code) $this->post('/email/verify',['otp'=>$code])->assertSessionHasErrors('otp');
        $this->post('/email/verify',['otp_digits'=>['0','0','7']])->assertSessionHasErrors('otp_digits');
        $this->post('/email/verify',['otp_digits'=>['0','0','x','7']])->assertSessionHasErrors('otp_digits.2');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
    public function test_expired_and_wrong_email_codes_cannot_verify(): void {
        $user=$this->pending(); $this->code($user,'1234',-1);
        $this->actingAs($user)->post('/email/verify',['otp'=>'1234'])->assertSessionHasErrors('otp');
        $this->code($user,'1234'); $user->update(['email'=>'changed@gmail.com']);
        $this->actingAs($user->fresh())->post('/email/verify',['otp'=>'1234'])->assertSessionHasErrors('otp');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
    public function test_five_failed_attempts_lock_even_the_correct_code(): void {
        $user=$this->pending(); $this->code($user,'1234'); $this->actingAs($user);
        for($i=0;$i<5;$i++) $this->post('/email/verify',['otp'=>'9999'])->assertSessionHasErrors('otp');
        $this->post('/email/verify',['otp'=>'1234'])->assertSessionHasErrors('otp');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertTrue(RateLimiter::tooManyAttempts('email-otp:'.$user->id.':attempts',5));
    }
    public function test_resend_emails_four_digits_and_respects_cooldown(): void {
        $code=null; Event::listen(MessageSending::class,function($event) use(&$code) { preg_match('/OTP is ([0-9]{4})\./',$event->message->getTextBody(),$matches); $code=$matches[1]??null; });
        $user=$this->pending('teacher'); $this->code($user,'9876');
        $old=Cache::get('email-otp:'.$user->id)['hash'];
        $this->actingAs($user)->post('/email/verification-notification')->assertSessionHasNoErrors();
        $this->assertMatchesRegularExpression('/^[0-9]{4}$/',$code);
        $new=Cache::get('email-otp:'.$user->id)['hash'];
        $this->assertNotSame($old,$new); $this->assertTrue(Hash::check($code,$new));
        $this->post('/email/verification-notification')->assertSessionHasErrors('otp');
        $this->assertSame($new,Cache::get('email-otp:'.$user->id)['hash']);
        $this->post('/email/verify',['otp'=>$code])->assertRedirect(route('dashboard'));
    }
    public function test_signup_validation_applies_to_both_roles(): void {
        foreach(['student','teacher'] as $role) {
            $this->post('/register',['name'=>'X','email'=>'someone@uc.edu.ph','role'=>$role,'password'=>'123','password_confirmation'=>'different'])->assertSessionHasErrors(['name','email','password']);
        }
        $this->post('/register',['name'=>'Student User','email'=>'student@gmail.com','role'=>'student','password'=>'Password123','password_confirmation'=>'Password123'])->assertSessionHasErrors('course');
        $this->assertDatabaseCount('users',0);
        $this->post('/register',['name'=>'Teacher User','email'=>'  TEACHER@gmail.com  ','role'=>'teacher','password'=>'Password123','password_confirmation'=>'Password123'])->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users',['email'=>'teacher@gmail.com']);
    }
    public function test_login_checks_role_status_and_normalizes_gmail_for_both_roles(): void {
        foreach(['student','teacher'] as $role) {
            $user=User::factory()->create(['role'=>$role,'email'=>$role.'@gmail.com','password'=>'Password123']);
            $path=$role==='teacher'?'/teacher/login':'/login'; $field=$role==='teacher'?'username':'login';
            $this->post($path,[$field=>$user->email,'password'=>'WrongPassword'])->assertSessionHasErrors($field);
            $this->assertGuest();
            $user->update(['status'=>'inactive']);
            $this->post($path,[$field=>$user->email,'password'=>'Password123'])->assertSessionHasErrors($field); $this->assertGuest();
            $user->update(['status'=>'active']);
            $this->post($path,[$field=>'  '.strtoupper($user->email).'  ','password'=>'Password123'])->assertRedirect(route($role.'.dashboard'));
            $this->post('/logout');
        }
    }
}
