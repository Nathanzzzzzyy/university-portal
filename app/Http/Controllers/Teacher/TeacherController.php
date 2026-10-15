<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class TeacherController extends Controller
{
    public function dashboard(Request $request)
    {
        $teacherId = $request->user()->id;
        $semester = \App\Http\Controllers\StudentController::SEMESTER;
        $classes = Subject::where('teacher_id', $teacherId)->withCount(['enrollments' => fn($q) => $q->where('semester', $semester)])->get();
        $work = \App\Models\Assignment::where('teacher_id', $teacherId);
        $submissions = \App\Models\Submission::whereHas('assignment', fn($q) => $q->where('teacher_id', $teacherId));
        return view('teacher.dashboard', [
            'classes' => $classes, 'semester' => $semester,
            'studentCount' => \App\Models\Enrollment::whereIn('subject_id', $classes->pluck('id'))->where('semester', $semester)->distinct()->count('user_id'),
            'assignmentCount' => (clone $work)->count(),
            'reviewCount' => (clone $submissions)->whereNotNull('submitted_at')->whereNull('graded_at')->count(),
            'missingCount' => (clone $submissions)->whereNull('submitted_at')->whereHas('assignment',fn($q)=>$q->where('due_at','<',now()))->count(),
            'reviewQueue' => (clone $submissions)->whereNotNull('submitted_at')->whereNull('graded_at')->with('student','assignment.subject')->oldest('submitted_at')->take(5)->get(),
            'upcoming' => (clone $work)->where('due_at','>=',now())->with('subject')->withCount('submissions')->orderBy('due_at')->take(4)->get(),
        ]);
    }

    public function settings(Request $request)
    {
        return view('teacher.settings', ['subjects' => Subject::where('teacher_id', $request->user()->id)->orderBy('code')->get()]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string', 'max:72', 'current_password'],
            'password' => ['required', 'string', 'max:72', 'different:current_password', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->update(['password' => $request->password]);

        return back()->with('status', 'Password changed.');
    }
}