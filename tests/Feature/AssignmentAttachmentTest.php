<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Subject;
use App\Models\Enrollment;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Attachment;
use App\Http\Controllers\StudentController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class AssignmentAttachmentTest extends TestCase
{
    use RefreshDatabase;
    private function setupClass(): array {
        Storage::fake('local');
        $teacher=User::factory()->create(['role'=>'teacher']);
        $student=User::factory()->create(['role'=>'student']);
        $subject=Subject::create(['teacher_id'=>$teacher->id,'code'=>'IT101','title'=>'Computing','units'=>3]);
        Enrollment::create(['user_id'=>$student->id,'subject_id'=>$subject->id,'semester'=>StudentController::SEMESTER]);
        return [$teacher,$student,$subject];
    }
    private function publish(User $teacher, Subject $subject, array $files=[]): void {
        $this->actingAs($teacher)->post('/teacher/assignments',[
            'subject_id'=>$subject->id,'title'=>'File assignment','instructions'=>'Read the materials and submit your work.',
            'due_at'=>now()->addDay()->toDateTimeString(),'points'=>100,'audience'=>'all','attachments'=>$files,
        ])->assertSessionHasNoErrors();
    }
    public function test_teacher_files_are_uploaded_and_downloadable_only_by_owner_and_recipients(): void {
        [$teacher,$student,$subject]=$this->setupClass();
        $this->publish($teacher,$subject,[UploadedFile::fake()->create('worksheet.pdf',10,'application/pdf'),UploadedFile::fake()->create('instructions.docx',10,'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),UploadedFile::fake()->create('diagram.png',10,'image/png')]);
        $this->assertDatabaseCount('attachments',3);
        foreach(Attachment::all() as $file) {
            Storage::disk('local')->assertExists($file->path);
            $this->actingAs($student)->get(route('attachments.download',$file))->assertOk()->assertDownload($file->name);
            $this->actingAs($teacher)->get(route('attachments.download',$file))->assertOk();
        }
        $this->actingAs($student)->get('/student/classwork')->assertOk()->assertSee('worksheet.pdf')->assertSee('instructions.docx')->assertSee('diagram.png');
        $this->actingAs($teacher)->get(route('teacher.assignments.show',Assignment::first()))->assertOk()->assertSee('worksheet.pdf');
        $outsider=User::factory()->create(['role'=>'student']);
        $this->actingAs($outsider)->get(route('attachments.download',Attachment::first()))->assertForbidden();
        $otherTeacher=User::factory()->create(['role'=>'teacher']);
        $this->actingAs($otherTeacher)->get(route('attachments.download',Attachment::first()))->assertForbidden();
    }
    public function test_student_can_submit_files_without_text_and_teacher_can_download_them(): void {
        [$teacher,$student,$subject]=$this->setupClass(); $this->publish($teacher,$subject);
        $submission=Submission::firstOrFail();
        $this->actingAs($student)->put(route('student.classwork.submit',$submission),['attachments'=>[UploadedFile::fake()->create('answer.docx',10,'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),UploadedFile::fake()->create('work.jpg',10,'image/jpeg')]])->assertSessionHasNoErrors();
        $this->assertNotNull($submission->fresh()->submitted_at);
        $this->assertDatabaseCount('attachments',2);
        $file=Attachment::firstOrFail();
        $this->actingAs($teacher)->get(route('attachments.download',$file))->assertOk()->assertDownload('answer.docx');
        $this->get(route('teacher.assignments.show',$submission->assignment))->assertOk()->assertSee('answer.docx')->assertSee('work.jpg');
        $peer=User::factory()->create(['role'=>'student']);
        $submission->assignment->submissions()->create(['student_id'=>$peer->id]);
        $this->actingAs($peer)->get(route('attachments.download',$file))->assertForbidden();
        $this->actingAs($teacher)->put(route('teacher.submissions.grade',$submission),['score'=>80])->assertSessionHasNoErrors();
        $this->actingAs($student)->get('/student/classwork')->assertOk()->assertSee('answer.docx')->assertSee('80.00 / 100');
        $this->put(route('student.classwork.submit',$submission),['attachments'=>[UploadedFile::fake()->create('new.pdf',10,'application/pdf')]])->assertStatus(422);
        $this->assertDatabaseCount('attachments',2);
    }
    public function test_submission_replacement_removes_old_files_and_no_upload_keeps_current_files(): void {
        [$teacher,$student,$subject]=$this->setupClass(); $this->publish($teacher,$subject); $submission=Submission::firstOrFail();
        $this->actingAs($student)->put(route('student.classwork.submit',$submission),['attachments'=>[UploadedFile::fake()->create('old.pdf',10,'application/pdf')]])->assertSessionHasNoErrors();
        $oldPath=Attachment::firstOrFail()->path;
        $this->put(route('student.classwork.submit',$submission),['answer'=>'Updated note'])->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists($oldPath);
        $this->put(route('student.classwork.submit',$submission),['attachments'=>[UploadedFile::fake()->create('replacement.pdf',10,'application/pdf')]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('attachments',1);
        $this->assertSame('replacement.pdf',Attachment::firstOrFail()->name);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists(Attachment::firstOrFail()->path);
    }
    public function test_invalid_uploads_and_empty_submission_are_rejected(): void {
        [$teacher,$student,$subject]=$this->setupClass(); $this->publish($teacher,$subject); $submission=Submission::firstOrFail();
        $this->actingAs($student)->put(route('student.classwork.submit',$submission),[])->assertSessionHasErrors('answer');
        foreach([UploadedFile::fake()->create('script.php',10,'application/pdf'),UploadedFile::fake()->create('too-large.pdf',5121,'application/pdf'),UploadedFile::fake()->create('fake.pdf',10,'text/plain')] as $file) {
            $this->put(route('student.classwork.submit',$submission),['attachments'=>[$file]])->assertSessionHasErrors('attachments.0');
        }
        $files=[]; for($i=0;$i<6;$i++) $files[]=UploadedFile::fake()->create('file'.$i.'.pdf',10,'application/pdf');
        $this->put(route('student.classwork.submit',$submission),['attachments'=>$files])->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('attachments',0);
        $this->assertNull($submission->fresh()->submitted_at);
    }
}
