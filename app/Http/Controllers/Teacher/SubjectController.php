<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Http\Requests\SubjectRequest;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function store(SubjectRequest $request)
    {
        $data = $request->validated();
        try {
            \App\Services\SubjectCover::save(new Subject, $request, $data + ['teacher_id' => $request->user()->id]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages(['code' => 'This subject code already exists. Choose another code.']);
        }

        return back()->with('status', 'Subject added.');
    }

    public function destroy(Request $request, Subject $subject)
    {
        abort_unless($subject->teacher_id === $request->user()->id, 403);
        if ($subject->enrollments()->exists() || \App\Models\Assignment::where('subject_id', $subject->id)->exists()) {
            return back()->withErrors(['subject' => 'This subject has student records. Keep it to preserve enrollments and work.']);
        }
        $oldCover = $subject->cover_photo_path;
        $subject->delete();
        if ($oldCover) \Illuminate\Support\Facades\Storage::disk('local')->delete($oldCover);

        return back()->with('status', 'Subject deleted.');
    }
}