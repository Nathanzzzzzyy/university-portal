<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Subject;
use App\Models\Enrollment;
use App\Http\Controllers\StudentController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class SubjectCoverTest extends TestCase
{
    use RefreshDatabase;
    private function photo(): UploadedFile {
        return UploadedFile::fake()->createWithContent('cover.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }
    private function subject(User $teacher): Subject { return Subject::create(['teacher_id'=>$teacher->id,'code'=>'IT101','title'=>'Introduction to IT','units'=>3]); }
    public function test_teacher_uploads_cover_and_both_portals_show_it(): void {
        Storage::fake('local'); $teacher=User::factory()->create(['role'=>'teacher']); $subject=$this->subject($teacher);
        $student=User::factory()->create(['role'=>'student']);
        Enrollment::create(['user_id'=>$student->id,'subject_id'=>$subject->id,'semester'=>StudentController::SEMESTER]);
        $this->actingAs($teacher)->put(route('teacher.classes.cover',$subject),['cover_photo'=>$this->photo()])->assertSessionHasNoErrors();
        $subject->refresh(); $this->assertNotNull($subject->cover_photo_path); Storage::disk('local')->assertExists($subject->cover_photo_path);
        $url=route('subjects.cover',$subject);
        $this->get('/teacher/classes')->assertOk()->assertSee($url,false);
        $this->get('/teacher')->assertOk()->assertSee($url,false);
        $this->get(route('teacher.classes.show',$subject))->assertOk()->assertSee('Save cover photo');
        $this->actingAs($student)->get('/student/subjects')->assertOk()->assertSee($url,false);
        $this->get('/student')->assertOk()->assertSee($url,false);
        $this->get($url)->assertOk()->assertHeader('Content-Type','image/png');
    }
    public function test_cover_can_be_replaced_and_removed_without_losing_subject(): void {
        Storage::fake('local'); $teacher=User::factory()->create(['role'=>'teacher']); $subject=$this->subject($teacher); $this->actingAs($teacher);
        $this->put(route('teacher.classes.cover',$subject),['cover_photo'=>$this->photo()])->assertSessionHasNoErrors();
        $old=$subject->fresh()->cover_photo_path;
        $this->put(route('teacher.classes.cover',$subject),['cover_photo'=>$this->photo()])->assertSessionHasNoErrors();
        $new=$subject->fresh()->cover_photo_path; $this->assertNotSame($old,$new); Storage::disk('local')->assertMissing($old); Storage::disk('local')->assertExists($new);
        $this->put(route('teacher.classes.cover',$subject),['remove_cover'=>'1'])->assertSessionHasNoErrors();
        $this->assertNull($subject->fresh()->cover_photo_path); Storage::disk('local')->assertMissing($new);
        $this->assertDatabaseHas('subjects',['id'=>$subject->id]);
    }
    public function test_invalid_upload_and_other_teacher_cannot_change_cover(): void {
        Storage::fake('local'); $teacher=User::factory()->create(['role'=>'teacher']); $subject=$this->subject($teacher);
        $this->actingAs($teacher)->put(route('teacher.classes.cover',$subject),['cover_photo'=>UploadedFile::fake()->createWithContent('cover.svg','<svg></svg>')])->assertSessionHasErrors('cover_photo');
        $this->put(route('teacher.classes.cover',$subject),['cover_photo'=>UploadedFile::fake()->create('big.png',5121,'image/png')])->assertSessionHasErrors('cover_photo');
        $this->put(route('teacher.classes.cover',$subject),[])->assertSessionHasErrors('cover_photo');
        $other=User::factory()->create(['role'=>'teacher']);
        $this->actingAs($other)->put(route('teacher.classes.cover',$subject),['cover_photo'=>$this->photo()])->assertForbidden();
        $this->assertNull($subject->fresh()->cover_photo_path);
        $this->assertCount(0,Storage::disk('local')->allFiles('subject-covers'));
    }
    public function test_new_subject_can_include_cover_and_outsiders_cannot_fetch_it(): void {
        Storage::fake('local'); $teacher=User::factory()->create(['role'=>'teacher']);
        $this->actingAs($teacher)->post('/teacher/subjects',['code'=>'CS101','title'=>'Introduction to Computing','units'=>3,'cover_photo'=>$this->photo()])->assertSessionHasNoErrors();
        $subject=Subject::firstOrFail(); $this->assertNotNull($subject->cover_photo_path);
        $outsider=User::factory()->create(['role'=>'student']);
        $this->actingAs($outsider)->get(route('subjects.cover',$subject))->assertForbidden();
        $other=User::factory()->create(['role'=>'teacher']);
        $this->actingAs($other)->get(route('subjects.cover',$subject))->assertForbidden();
    }
}
