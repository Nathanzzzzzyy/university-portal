<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SubjectValidationTest extends TestCase
{
    use RefreshDatabase;
    public function test_valid_subject_input_is_normalized_and_owned_by_the_teacher(): void {
        $teacher=User::factory()->create(['role'=>'teacher']);
        $this->actingAs($teacher)->from('/teacher/settings')->post('/teacher/subjects',['code'=>' cs-101 ','title'=>' Introduction   to Computing ','units'=>3,'schedule_day'=>'MWF','schedule_time'=>'8:00 AM - 9:00 AM','room'=>' Lab 1 '])->assertRedirect('/teacher/settings')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subjects',['code'=>'CS-101','title'=>'Introduction to Computing','room'=>'Lab 1','teacher_id'=>$teacher->id]);
        $this->get('/teacher/settings')->assertOk()->assertSee('subject-days')->assertSee('subject-code');
    }
    public function test_bad_codes_titles_units_days_times_and_rooms_are_rejected(): void {
        $teacher=User::factory()->create(['role'=>'teacher']); $this->actingAs($teacher);
        $base=['code'=>'IT101','title'=>'Introduction to IT','units'=>3];
        foreach ([['code'=>'12345'],['code'=>'IT@101'],['title'=>'12345'],['title'=>'<script>bad</script>'],['units'=>0],['units'=>7],['units'=>2.5],['schedule_day'=>'Everyday','schedule_time'=>'8:00 AM - 9:00 AM'],['schedule_day'=>'MWF','schedule_time'=>'25:00 - 26:00'],['schedule_day'=>'MWF','schedule_time'=>'9:60 AM - 10:00 AM'],['schedule_day'=>'MWF','schedule_time'=>'10:00 AM - 9:00 AM'],['schedule_day'=>'MWF','schedule_time'=>'8:00 AM - 8:00 AM'],['room'=>'<Lab>']] as $invalid) {
            $this->post('/teacher/subjects',array_replace($base,$invalid))->assertSessionHasErrors(isset($invalid['schedule_time']) && ($invalid['schedule_day'] ?? '') !== 'Everyday' ? 'schedule_time' : array_key_first($invalid));
        }
        $this->assertDatabaseCount('subjects',0);
    }
    public function test_schedule_requires_both_days_and_time_but_can_be_unscheduled(): void {
        $teacher=User::factory()->create(['role'=>'teacher']); $this->actingAs($teacher);
        $base=['code'=>'IT101','title'=>'Introduction to IT','units'=>3];
        $this->post('/teacher/subjects',$base+['schedule_day'=>'MWF'])->assertSessionHasErrors('schedule_time');
        $this->post('/teacher/subjects',$base+['schedule_time'=>'13:00 - 14:30'])->assertSessionHasErrors('schedule_day');
        $this->post('/teacher/subjects',$base)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('subjects',1);
    }
    public function test_duplicate_codes_are_rejected_including_legacy_lowercase(): void {
        $teacher=User::factory()->create(['role'=>'teacher']);
        Subject::create(['code'=>'it101','title'=>'Existing subject','units'=>3]);
        $this->actingAs($teacher)->post('/teacher/subjects',['code'=>' IT101 ','title'=>'Another subject','units'=>3])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('subjects',1);
    }
    public function test_class_edit_uses_same_rules_and_accepts_valid_24_hour_times(): void {
        $teacher=User::factory()->create(['role'=>'teacher']);
        $subject=Subject::create(['teacher_id'=>$teacher->id,'code'=>'IT101','title'=>'Introduction to IT','units'=>3]);
        $this->actingAs($teacher)->put(route('teacher.classes.update',$subject),['title'=>'Introduction to IT','units'=>3,'schedule_day'=>'Tuesday','schedule_time'=>'15:00 - 14:00'])->assertSessionHasErrors('schedule_time');
        $this->put(route('teacher.classes.update',$subject),['title'=>'Introduction to IT','units'=>3,'schedule_day'=>'Tuesday','schedule_time'=>'13:00 - 14:30'])->assertSessionHasNoErrors();
        $this->assertSame('13:00 - 14:30',$subject->fresh()->schedule_time);
    }
    public function test_password_settings_validate_current_confirmation_and_reuse(): void {
        $teacher=User::factory()->create(['role'=>'teacher','password'=>'Password123']); $this->actingAs($teacher);
        $this->put('/teacher/settings/password',['current_password'=>'WrongPassword','password'=>'NewPassword123','password_confirmation'=>'NewPassword123'])->assertSessionHasErrors('current_password');
        $this->put('/teacher/settings/password',['current_password'=>'Password123','password'=>'Password123','password_confirmation'=>'Password123'])->assertSessionHasErrors('password');
        $this->put('/teacher/settings/password',['current_password'=>'Password123','password'=>'NewPassword123','password_confirmation'=>'Mismatch123'])->assertSessionHasErrors('password');
        $this->put('/teacher/settings/password',['current_password'=>'Password123','password'=>'NewPassword123','password_confirmation'=>'NewPassword123'])->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword123',$teacher->fresh()->password));
    }
}
