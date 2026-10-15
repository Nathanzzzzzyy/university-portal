<?php
namespace App\Http\Controllers;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class SubjectCoverController extends Controller {
    public function show(Request $request, Subject $subject) {
        $user=$request->user();
        $allowed=($user->role==='teacher' && $subject->teacher_id===$user->id)
            || ($user->role==='student' && $subject->enrollments()->where('user_id',$user->id)->exists());
        abort_unless($allowed,403);
        abort_unless($subject->cover_photo_path && Storage::disk('local')->exists($subject->cover_photo_path),404);
        return response()->file(Storage::disk('local')->path($subject->cover_photo_path),['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}
