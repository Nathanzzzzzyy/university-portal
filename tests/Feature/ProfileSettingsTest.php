<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;
    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'year_level' => '1st Year', 'student_number' => '2026-0099', 'email' => 'student@gmail.com']);
    }
    public function test_personal_details_change_but_campus_records_cannot_be_overwritten(): void
    {
        $user = $this->student();
        $this->actingAs($user)->put('/student/profile', ['name' => 'Updated Student', 'email' => $user->email, 'address' => 'New address', 'contact_number' => '09123456789', 'course' => 'BSCS', 'year_level' => '4th Year', 'student_number' => 'FAKE', 'role' => 'teacher', 'status' => 'inactive'])->assertRedirect(route('student.profile'));
        $user->refresh();
        $this->assertSame('Updated Student', $user->name);
        $this->assertSame('New address', $user->address);
        $this->assertSame('BSIT', $user->course);
        $this->assertSame('1st Year', $user->year_level);
        $this->assertSame('2026-0099', $user->student_number);
        $this->assertSame('student', $user->role);
        $this->assertSame('active', $user->status);
    }
    public function test_photo_is_uploaded_served_privately_and_removed(): void
    {
        Storage::fake('local');
        $user = $this->student();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->actingAs($user)->put('/student/profile', ['name' => $user->name, 'email' => $user->email, 'photo' => UploadedFile::fake()->createWithContent('avatar.png', $png)])->assertSessionHasNoErrors();
        $path = $user->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        $this->actingAs($user->fresh())->get('/account/photo')->assertOk();
        $this->put('/student/profile', ['name' => $user->name, 'email' => $user->email, 'remove_photo' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('local')->assertMissing($path);
    }
    public function test_non_image_upload_is_rejected(): void
    {
        $user = $this->student();
        $this->actingAs($user)->put('/student/profile', ['name' => $user->name, 'email' => $user->email, 'photo' => UploadedFile::fake()->createWithContent('avatar.svg', '<svg></svg>')])->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->profile_photo_path);
    }
    public function test_email_changes_require_new_otp_verification(): void
    {
        config(['mail.default' => 'array']);
        $user = $this->student();
        $this->actingAs($user)->put('/student/profile', ['name' => $user->name, 'email' => 'newaddress@gmail.com'])->assertRedirect(route('verification.notice'));
        $this->assertNull($user->fresh()->email_verified_at);
        $this->actingAs($user->fresh())->get('/student')->assertRedirect(route('verification.notice'));
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has('email-otp:'.$user->id));
    }
    public function test_appearance_is_saved_and_rendered_on_other_pages(): void
    {
        $user = $this->student();
        $this->actingAs($user)->put('/student/settings/preferences', ['theme' => 'dark', 'density' => 'compact', 'text_size' => 'large', 'reduce_motion' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->preferences['reduce_motion']);
        $this->actingAs($user->fresh())->get('/student/profile')->assertOk()->assertSee('data-theme="dark"', false)->assertSee('data-density="compact"', false)->assertSee('data-text-size="large"', false)->assertSee('data-reduce-motion="true"', false);
        $this->put('/student/settings/preferences', ['theme' => 'invalid', 'density' => 'compact', 'text_size' => 'large', 'reduce_motion' => '1'])->assertSessionHasErrors('theme');
        $this->assertSame('dark', $user->fresh()->preferences['theme']);
    }
}
