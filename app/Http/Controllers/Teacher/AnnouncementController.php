<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Subject;
use App\Http\Controllers\StudentController;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        return view('teacher.announcements.index', ['items' => Announcement::where('teacher_id', $request->user()->id)->with('subject')->latest()->paginate(10)]);
    }

    public function create(Request $request)
    {
        return view('teacher.announcements.form', ['item' => new Announcement(['status' => 'published']), 'classes' => $this->classes($request)]);
    }

    public function store(Request $request)
    {
        Announcement::create($this->data($request) + ['teacher_id' => $request->user()->id]);

        return redirect()->route('teacher.announcements.index')->with('status', 'Announcement created.');
    }

    public function edit(Request $request, Announcement $announcement)
    {
        abort_unless($announcement->teacher_id === $request->user()->id, 403);
        return view('teacher.announcements.form', ['item' => $announcement, 'classes' => $this->classes($request)]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        abort_unless($announcement->teacher_id === $request->user()->id, 403);
        $announcement->update($this->data($request));

        return redirect()->route('teacher.announcements.index')->with('status', 'Announcement updated.');
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        abort_unless($announcement->teacher_id === $request->user()->id, 403);
        $announcement->delete();

        return back()->with('status', 'Announcement deleted.');
    }

    private function classes(Request $request)
    {
        return Subject::where('teacher_id', $request->user()->id)->orderBy('code')->get();
    }

    private function data(Request $request): array
    {
        $d = $request->validate([
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')->where('teacher_id', $request->user()->id)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:published,draft'],
        ]);
        $d['semester'] = StudentController::SEMESTER;
        $d['published_at'] = $d['status'] === 'published' ? now() : null;

        return $d;
    }
}