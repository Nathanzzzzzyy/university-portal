<?php
namespace Tests\Feature;
use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use App\Http\Controllers\StudentController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ClassAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_class_roster_sees_published_announcement_on_both_student_pages(): void
    {
        $teacher=User::factory()->create(['role'=>'teacher']);
        $class=Subject::create(['teacher_id'=>$teacher->id,'code'=>'IT101','title'=>'Computing','units'=>3]);
        $member=User::factory()->create(['role'=>'student']);
        $outsider=User::factory()->create(['role'=>'student']);
        $pastMember=User::factory()->create(['role'=>'student']);
        Enrollment::create(['user_id'=>$member->id,'subject_id'=>$class->id,'semester'=>StudentController::SEMESTER]);
        Enrollment::create(['user_id'=>$pastMember->id,'subject_id'=>$class->id,'semester'=>'Previous semester']);
        $this->actingAs($teacher)->post('/teacher/announcements',['subject_id'=>$class->id,'title'=>'Only our computing class','content'=>'Private class update.','status'=>'published'])->assertSessionHasNoErrors();
        Announcement::create(['subject_id'=>$class->id,'teacher_id'=>$teacher->id,'semester'=>StudentController::SEMESTER,'title'=>'Unpublished draft','content'=>'Not ready.','status'=>'draft']);
        Announcement::create(['teacher_id'=>$teacher->id,'title'=>'Legacy unscoped teacher notice','content'=>'Old message.','status'=>'published','published_at'=>now()]);
        foreach (['/student','/student/announcements'] as $url) {
            $this->actingAs($member)->get($url)->assertOk()->assertSee('Only our computing class')->assertDontSee('Unpublished draft')->assertDontSee('Legacy unscoped teacher notice');
            $this->actingAs($outsider)->get($url)->assertOk()->assertDontSee('Only our computing class');
            $this->actingAs($pastMember)->get($url)->assertOk()->assertDontSee('Only our computing class');
        }
    }

    public function test_teacher_must_select_own_class_when_creating_or_updating(): void
    {
        $teacher=User::factory()->create(['role'=>'teacher']);
        $other=User::factory()->create(['role'=>'teacher']);
        $own=Subject::create(['teacher_id'=>$teacher->id,'code'=>'OWN','title'=>'Own class','units'=>3]);
        $foreign=Subject::create(['teacher_id'=>$other->id,'code'=>'OTHER','title'=>'Other class','units'=>3]);
        $data=['title'=>'Class update','content'=>'Notes','status'=>'published'];
        $this->actingAs($teacher)->post('/teacher/announcements',$data)->assertSessionHasErrors('subject_id');
        $this->post('/teacher/announcements',$data+['subject_id'=>$foreign->id])->assertSessionHasErrors('subject_id');
        $this->assertDatabaseCount('announcements',0);
        $this->get('/teacher/announcements/create')->assertOk()->assertSee('Own class')->assertDontSee('Other class');
        $this->post('/teacher/announcements',$data+['subject_id'=>$own->id])->assertSessionHasNoErrors();
        $announcement=Announcement::firstOrFail();
        $this->put(route('teacher.announcements.update',$announcement),$data+['subject_id'=>$foreign->id])->assertSessionHasErrors('subject_id');
        $this->assertSame($own->id,$announcement->fresh()->subject_id);
    }
}
