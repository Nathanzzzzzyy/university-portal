<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function index(Request $request)
    {
        $students = User::where('role', 'student')->whereHas('enrollments.subject', fn($q) => $q->where('teacher_id', $request->user()->id))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('student_number', 'like', "%$s%")))
            ->withCount(['enrollments' => fn($q) => $q->whereHas('subject', fn($s) => $s->where('teacher_id', $request->user()->id))])->orderBy('name')->paginate(10)->withQueryString();

        return view('teacher.records.index', compact('students'));
    }

    public function show(Request $request, User $user)
    {
        abort_if($user->isTeacher(), 404);
        abort_unless($user->enrollments()->whereHas('subject', fn($q) => $q->where('teacher_id', $request->user()->id))->exists(), 403);

        return view('teacher.records.show', [
            'student' => $user,
            'enrollments' => $user->enrollments()->whereHas('subject', fn($q) => $q->where('teacher_id', $request->user()->id))->with('subject')->get(),
        ]);
    }

    public function grade(Request $request, Enrollment $enrollment)
    {
        abort_unless($enrollment->subject->teacher_id === $request->user()->id, 403);
        $data = $request->validate(['grade' => ['nullable', 'numeric', 'between:1,5']]);
        $enrollment->update(['grade' => $data['grade'] ?? null]);

        return back()->with('status', 'Grade saved.');
    }
}