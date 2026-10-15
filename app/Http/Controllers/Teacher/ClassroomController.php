<?php
namespace App\Http\Controllers\Teacher;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StudentController;
use App\Models\Subject;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class ClassroomController extends Controller {
    public function index(Request $request) {
        return view('teacher.classes.index', [
            'classes'=>Subject::where('teacher_id',$request->user()->id)->withCount(['enrollments'=>fn($q)=>$q->where('semester',StudentController::SEMESTER)])->get(),
            'unassigned'=>Subject::whereNull('teacher_id')->orderBy('code')->get(), 'semester'=>StudentController::SEMESTER,
        ]);
    }
    public function claim(Request $request, Subject $subject) {
        $changed=Subject::whereKey($subject->id)->whereNull('teacher_id')->update(['teacher_id'=>$request->user()->id]);
        abort_unless($changed,403,'This class already belongs to a teacher.');
        return redirect()->route('teacher.classes.show',$subject)->with('status','Class added to your teaching load.');
    }
    public function show(Request $request, Subject $subject) {
        abort_unless($subject->teacher_id === $request->user()->id,403);
        $roster=$subject->enrollments()->where('semester',StudentController::SEMESTER)->with('user')->get();
        return view('teacher.classes.show',['subject'=>$subject,'roster'=>$roster,'students'=>User::where('role','student')->where('status','active')->whereNotIn('id',$roster->pluck('user_id'))->orderBy('name')->get(),'semester'=>StudentController::SEMESTER]);
    }
    public function update(\App\Http\Requests\SubjectRequest $request, Subject $subject) {
        abort_unless($subject->teacher_id === $request->user()->id,403);
        $data=$request->validated();
        \App\Services\SubjectCover::save($subject, $request, $data);
        return back()->with('status','Class details saved. Student subjects and schedules are updated.');
    }
    public function cover(Request $request, Subject $subject) {
        abort_unless($subject->teacher_id === $request->user()->id,403);
        $request->validate(\App\Services\SubjectCover::rules());
        if (!$request->hasFile('cover_photo') && !$request->boolean('remove_cover')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['cover_photo'=>'Choose a cover photo or select remove cover.']);
        }
        \App\Services\SubjectCover::save($subject, $request);
        return back()->with('status','Subject cover updated. Your students can now see it in their portal.');
    }
    public function enroll(Request $request, Subject $subject) {
        abort_unless($subject->teacher_id === $request->user()->id,403);
        $data=$request->validate(['student_id'=>['required',Rule::exists('users','id')->where('role','student')->where('status','active')]]);
        Enrollment::firstOrCreate(['user_id'=>$data['student_id'],'subject_id'=>$subject->id,'semester'=>StudentController::SEMESTER]);
        return back()->with('status','Student added. This class now appears in their subjects and schedule.');
    }
}
