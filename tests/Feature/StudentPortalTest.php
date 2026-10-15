<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Subject;
use App\Models\Enrollment;
use App\Models\Announcement;
use App\Http\Controllers\StudentController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_pages_render_with_no_enrollments(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user);
        foreach (['/student', '/student/subjects', '/student/enrollment', '/student/grades', '/student/schedule', '/student/announcements', '/student/profile', '/student/settings'] as $url) {
            $this->get($url)->assertOk()->assertSee('student.css');
        }
        $this->get('/student')->assertSee('Your learning journey starts here')->assertSee('images/campus.png');
        $this->get('/student/subjects')->assertSee('Your next chapter is waiting');
    }

    public function test_dashboard_and_subject_collection_render_real_academic_data(): void
    {
        $user = User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'year_level' => '1st Year']);
        $subject = Subject::create(['code' => 'IT101', 'title' => 'Introduction to Computing', 'units' => 3, 'schedule_day' => 'Monday', 'schedule_time' => '08:00 - 09:30', 'room' => 'Lab 2']);
        Enrollment::create(['user_id' => $user->id, 'subject_id' => $subject->id, 'semester' => StudentController::SEMESTER, 'grade' => 1.75]);
        Announcement::create(['title' => 'Campus update', 'content' => 'Welcome to a new semester.', 'status' => 'published', 'published_at' => now()]);
        $this->actingAs($user)->get('/student')->assertOk()->assertSee('Introduction to Computing')->assertSee('Campus update')->assertSee('1.75');
        $this->get('/student/subjects')->assertOk()->assertSee('subject-search')->assertSee('data-status="graded"', false)->assertSee('Lab 2')->assertSee('Monday')->assertSee('Grade 1.75');
    }
}
