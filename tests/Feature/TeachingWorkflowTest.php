<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Subject;
use App\Models\Enrollment;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Announcement;
use App\Http\Controllers\StudentController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class TeachingWorkflowTest extends TestCase {
    use RefreshDatabase;
    private function teacher(): User { return User::factory()->create(['role'=>'teacher']); }
    private function subject(?User $teacher=null): Subject { return Subject::create(['teacher_id'=>$teacher?->id,'code'=>'IT'.random_int(100,999999),'title'=>'Computing Fundamentals','units'=>3,'schedule_day'=>'MWF','room'=>'Lab 1']); }
    private function enroll(User $student, Subject $subject): Enrollment { return Enrollment::create(['user_id'=>$student->id,'subject_id'=>$subject->id,'semester'=>StudentController::SEMESTER]); }
    private function payload(Subject $subject): array { return ['subject_id'=>$subject->id,'title'=>'Reflection task','instructions'=>'Explain what you learned.','due_at'=>now()->addDays(2)->format('Y-m-d H:i:s'),'points'=>100,'audience'=>'all']; }
    public function test_teacher_can_claim_class_enroll_student_and_assign_review_and_return_work(): void {
        $teacher=$this->teacher(); $student=User::factory()->create(['role'=>'student']); $subject=$this->subject();
        $this->actingAs($teacher)->post(route('teacher.classes.claim',$subject))->assertRedirect(route('teacher.classes.show',$subject));
        $this->post(route('teacher.classes.enroll',$subject),['student_id'=>$student->id])->assertSessionHasNoErrors();
        $this->post(route('teacher.classes.enroll',$subject),['student_id'=>$student->id])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('enrollments',1);
        $this->put(route('teacher.classes.update',$subject),['title'=>'Computing Fundamentals','units'=>3,'schedule_day'=>'Tuesday','schedule_time'=>'9:00 - 10:00 AM','room'=>'Lab 9'])->assertSessionHasNoErrors();
        foreach(['/teacher','/teacher/classes',route('teacher.classes.show',$subject),'/teacher/assignments','/teacher/assignments/create?subject_id='.$subject->id,'/teacher/records','/teacher/settings','/teacher/announcements','/teacher/announcements/create'] as $url) $this->get($url)->assertOk();
        $this->post('/teacher/assignments',$this->payload($subject))->assertSessionHasNoErrors();
        $assignment=Assignment::firstOrFail(); $submission=Submission::firstOrFail();
        $this->get(route('teacher.assignments.show',$assignment))->assertOk()->assertSee('Reflection task');
        $this->actingAs($student)->get('/student/subjects')->assertOk()->assertSee('Computing Fundamentals');
        $this->get('/student/schedule')->assertOk()->assertSee('Lab 9')->assertSee('Tuesday');
        $this->get('/student/classwork')->assertOk()->assertSee('Reflection task');
        $this->get('/student')->assertOk()->assertSee('Assignments to work on')->assertSee('Reflection task');
        $this->put(route('student.classwork.submit',$submission),['answer'=>'I learned about computing.'])->assertSessionHasNoErrors();
        $this->actingAs($teacher)->get('/teacher')->assertOk()->assertSee($student->name);
        $this->put(route('teacher.submissions.grade',$submission),['score'=>90,'feedback'=>'Thoughtful response.'])->assertSessionHasNoErrors();
        $this->actingAs($student)->get('/student/classwork')->assertOk()->assertSee('90.00 / 100')->assertSee('Thoughtful response.');
        $this->put(route('student.classwork.submit',$submission),['answer'=>'Replace returned work'])->assertStatus(422);
        $enrollment=Enrollment::firstOrFail();
        $this->actingAs($teacher)->put(route('teacher.records.grade',$enrollment),['grade'=>1.75])->assertSessionHasNoErrors();
        $this->actingAs($student)->get('/student/grades')->assertOk()->assertSee('1.75');
    }
    public function test_selected_students_receive_work_and_unrelated_accounts_are_denied(): void {
        $teacher=$this->teacher(); $other=$this->teacher(); $student=User::factory()->create(['role'=>'student']); $excluded=User::factory()->create(['role'=>'student']); $subject=$this->subject($teacher);
        $enrollment=$this->enroll($student,$subject); $this->enroll($excluded,$subject);
        $data=$this->payload($subject)+['student_ids'=>[$student->id]]; $data['audience']='selected';
        $this->actingAs($teacher)->post('/teacher/assignments',$data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('submissions',1); $assignment=Assignment::firstOrFail(); $submission=Submission::firstOrFail();
        $this->actingAs($excluded)->get('/student/classwork')->assertDontSee('Reflection task');
        $this->put(route('student.classwork.submit',$submission),['answer'=>'Not mine'])->assertForbidden();
        $this->actingAs($other)->get(route('teacher.classes.show',$subject))->assertForbidden();
        $this->post(route('teacher.classes.claim',$subject))->assertForbidden();
        $this->put(route('teacher.classes.update',$subject),['title'=>'Wrong owner','units'=>3])->assertForbidden();
        $this->post(route('teacher.classes.enroll',$subject),['student_id'=>$excluded->id])->assertForbidden();
        $this->get(route('teacher.assignments.show',$assignment))->assertForbidden();
        $this->put(route('teacher.submissions.grade',$submission),['score'=>50])->assertForbidden();
        $this->put(route('teacher.records.grade',$enrollment),['grade'=>1])->assertForbidden();
        $this->get(route('teacher.records.show',$student))->assertForbidden();
        $this->delete(route('teacher.subjects.destroy',$subject))->assertForbidden();
        $this->post('/teacher/assignments',$this->payload($subject))->assertSessionHasErrors('subject_id');
        $this->get('/teacher/users')->assertNotFound();
        $this->actingAs($student)->get('/teacher/classes')->assertRedirect(route('teacher.login'));
    }
    public function test_assignment_and_grade_validation_and_late_status(): void {
        $teacher=$this->teacher(); $student=User::factory()->create(['role'=>'student']); $outsider=User::factory()->create(['role'=>'student']); $subject=$this->subject($teacher); $this->enroll($student,$subject);
        $data=$this->payload($subject); $data['audience']='selected'; $data['student_ids']=[$outsider->id];
        $this->actingAs($teacher)->post('/teacher/assignments',$data)->assertSessionHasErrors('student_ids');
        $data=$this->payload($subject); $data['due_at']=now()->subDay()->toDateTimeString();
        $this->post('/teacher/assignments',$data)->assertSessionHasErrors('due_at');
        $this->post('/teacher/assignments',$this->payload($subject))->assertSessionHasNoErrors();
        $assignment=Assignment::firstOrFail(); $submission=Submission::firstOrFail();
        $this->put(route('teacher.submissions.grade',$submission),['score'=>90])->assertStatus(422);
        $assignment->update(['due_at'=>now()->subHour()]);
        $this->actingAs($student)->get('/student/classwork')->assertSee('Missing');
        $this->put(route('student.classwork.submit',$submission),['answer'=>'My late response'])->assertSessionHasNoErrors();
        $this->get('/student/classwork')->assertSee('Submitted late');
        $this->actingAs($teacher)->put(route('teacher.submissions.grade',$submission),['score'=>101])->assertSessionHasErrors('score');
        $this->put(route('teacher.submissions.grade',$submission),['score'=>0,'feedback'=>'Please review the instructions.'])->assertSessionHasNoErrors();
        $this->assertSame('0.00',$submission->fresh()->score);
        $this->delete(route('teacher.subjects.destroy',$subject))->assertSessionHasErrors('subject');
        $this->assertDatabaseHas('subjects',['id'=>$subject->id]);
    }
    public function test_new_teacher_can_create_class_and_publish_announcement(): void {
        $teacher=$this->teacher();
        $this->actingAs($teacher)->get('/teacher/assignments/create')->assertOk()->assertSee('Start with a class');
        $this->post('/teacher/subjects',['code'=>'CS101','title'=>'Introduction to Computing','units'=>3,'schedule_day'=>'MWF','schedule_time'=>'8:00 - 9:00 AM','room'=>'Lab 3'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subjects',['code'=>'CS101','teacher_id'=>$teacher->id]);
        $this->post('/teacher/announcements',['subject_id'=>Subject::where('code','CS101')->firstOrFail()->id,'title'=>'Welcome to class','content'=>'Please review your classwork.','status'=>'published'])->assertRedirect(route('teacher.announcements.index'));
        $this->assertDatabaseHas('announcements',['title'=>'Welcome to class','teacher_id'=>$teacher->id]);
        $student=User::factory()->create(['role'=>'student']);
        $this->enroll($student, Subject::where('code','CS101')->firstOrFail());
        $this->actingAs($student)->get('/student/announcements')->assertOk()->assertSee('Welcome to class');
    }
    public function test_teacher_cannot_edit_other_teachers_announcements(): void {
        $teacher=$this->teacher(); $other=$this->teacher();
        $announcement=Announcement::create(['teacher_id'=>$teacher->id,'title'=>'Class notice','content'=>'Bring your notes.','status'=>'published','published_at'=>now()]);
        $this->actingAs($other)->get(route('teacher.announcements.edit',$announcement))->assertForbidden();
        $this->put(route('teacher.announcements.update',$announcement),['title'=>'Changed','content'=>'Changed','status'=>'published'])->assertForbidden();
        $this->delete(route('teacher.announcements.destroy',$announcement))->assertForbidden();
    }
}
