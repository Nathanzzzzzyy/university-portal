<?php
namespace App\Http\Controllers;
use App\Models\Submission;
use App\Services\AssignmentFiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
class ClassworkController extends Controller {
    public function index(Request $request) {
        return view('student.classwork',['items'=>Submission::where('student_id',$request->user()->id)->with('assignment.subject','assignment.teacher','assignment.attachments','attachments')->latest()->paginate(12)]);
    }
    public function submit(Request $request, Submission $submission) {
        abort_unless($submission->student_id === $request->user()->id,403);
        abort_if($submission->graded_at,422,'This work has been returned and is locked.');
        $data=$request->validate(['answer'=>['nullable','string','max:30000']] + AssignmentFiles::rules());
        $paths = []; $oldPaths = [];
        try {
            DB::transaction(function () use ($request, $submission, $data, &$paths, &$oldPaths) {
                $locked = Submission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
                abort_if($locked->graded_at,422,'This work has been returned and is locked.');
                $files = $request->file('attachments', []);
                if (! trim($data['answer'] ?? '') && ! $files && ! $locked->attachments()->exists()) {
                    throw ValidationException::withMessages(['answer' => 'Write an answer or upload at least one file.']);
                }
                if ($files) {
                    $oldPaths = $locked->attachments()->pluck('path')->all();
                    $locked->attachments()->delete();
                    AssignmentFiles::store($files, $locked->assignment, $locked, $paths);
                }
                $locked->update(['answer' => $data['answer'] ?? null, 'submitted_at' => now()]);
            });
        } catch (\Throwable $e) {
            AssignmentFiles::deletePaths($paths);
            throw $e;
        }
        AssignmentFiles::deletePaths($oldPaths);
        return back()->with('status','Your work has been submitted to your teacher.');
    }
}
