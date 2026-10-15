<?php
namespace App\Http\Controllers;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class AttachmentController extends Controller
{
    public function download(Request $request, Attachment $attachment)
    {
        $user = $request->user();
        $assignment = $attachment->assignment;
        $allowed = $user->role === 'teacher' && $assignment->teacher_id === $user->id;
        if ($user->role === 'student') {
            $allowed = $attachment->submission_id
                ? $attachment->submission?->student_id === $user->id
                : $assignment->submissions()->where('student_id', $user->id)->exists();
        }
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);
        return Storage::disk('local')->download($attachment->path, $attachment->name, [
            'Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }
}
