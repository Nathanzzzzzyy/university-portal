<?php
namespace App\Http\Controllers\Teacher;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StudentController;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Subject;
use App\Services\AssignmentFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class AssignmentController extends Controller {
    public function index(Request $request) {
        return view('teacher.assignments.index',['assignments'=>Assignment::where('teacher_id',$request->user()->id)->with('subject')->withCount(['submissions','submissions as turned_in_count'=>fn($q)=>$q->whereNotNull('submitted_at'),'submissions as returned_count'=>fn($q)=>$q->whereNotNull('graded_at')])->latest()->paginate(12)]);
    }
    public function create(Request $request) {
        $classes=Subject::where('teacher_id',$request->user()->id)->orderBy('code')->get();
        $subject=$classes->firstWhere('id',(int)$request->input('subject_id')) ?? $classes->first();
        return view('teacher.assignments.create',['classes'=>$classes,'subject'=>$subject,'roster'=>$subject ? $subject->enrollments()->where('semester',StudentController::SEMESTER)->with('user')->get() : collect()]);
    }
    public function store(Request $request) {
        $data=$request->validate([
            'subject_id'=>['required',Rule::exists('subjects','id')->where('teacher_id',$request->user()->id)],
            'title'=>['required','string','max:255'],'instructions'=>['required','string','max:20000'],
            'due_at'=>['required','date','after:now'],'points'=>['required','integer','between:1,10000'],
            'audience'=>['required','in:all,selected'],'student_ids'=>['required_if:audience,selected','array'], 'student_ids.*'=>['integer','distinct'],
        ] + AssignmentFiles::rules());
        $roster=\App\Models\Enrollment::where('subject_id',$data['subject_id'])->where('semester',StudentController::SEMESTER)->whereHas('user',fn($q)=>$q->where('role','student')->where('status','active'))->pluck('user_id');
        $recipients=$data['audience']==='all' ? $roster : collect($data['student_ids'] ?? [])->map(fn($id)=>(int)$id);
        if($recipients->isEmpty() || $recipients->diff($roster)->isNotEmpty()) throw ValidationException::withMessages(['student_ids'=>'Select at least one active student enrolled in this class.']);
        $paths = [];
        try {
        $assignment=DB::transaction(function() use($data,$request,$recipients,&$paths) {
            $assignment=Assignment::create(collect($data)->only(['subject_id','title','instructions','due_at','points'])->all()+['teacher_id'=>$request->user()->id,'semester'=>StudentController::SEMESTER]);
            foreach($recipients as $id) $assignment->submissions()->create(['student_id'=>$id]);
            AssignmentFiles::store($request->file('attachments', []), $assignment, null, $paths);
            return $assignment;
        });
        } catch (\Throwable $e) {
            AssignmentFiles::deletePaths($paths);
            throw $e;
        }
        return redirect()->route('teacher.assignments.show',$assignment)->with('status','Assignment published to '.$recipients->count().' student(s).');
    }
    public function show(Request $request, Assignment $assignment) {
        abort_unless($assignment->teacher_id === $request->user()->id,403);
        return view('teacher.assignments.show',['assignment'=>$assignment->load('subject','attachments','submissions.student','submissions.attachments')]);
    }
    public function grade(Request $request, Submission $submission) {
        abort_unless($submission->assignment->teacher_id === $request->user()->id,403);
        abort_unless($submission->submitted_at,422,'The student has not submitted work yet.');
        $data=$request->validate(['score'=>['required','numeric','min:0','max:'.$submission->assignment->points],'feedback'=>['nullable','string','max:5000']]);
        $submission->update($data+['graded_at'=>now()]);
        return back()->with('status','Score and feedback returned to the student.');
    }
}
