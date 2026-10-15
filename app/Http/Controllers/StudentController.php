<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public const SEMESTER = '1st Semester 2025 - 2026';

    /** /dashboard sends each role to the right place */
    public function redirect(Request $request)
    {
        return $request->user()->isTeacher()
            ? redirect()->route('teacher.dashboard')
            : redirect()->route('student.dashboard');
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $enrollments = $user->enrollments()->with('subject')->where('semester', self::SEMESTER)->get();

        return view('student.dashboard', [
            'user' => $user,
            'enrollments' => $enrollments,
            'units' => $enrollments->sum(fn ($e) => $e->subject->units),
            'gpa' => $user->gpa(),
            'announcements' => Announcement::published()->visibleTo($user)->with('subject')->take(4)->get(),
            'classwork' => \App\Models\Submission::where('student_id',$user->id)->whereNull('graded_at')->whereNull('submitted_at')->with('assignment.subject')->get()->sortBy(fn($submission) => $submission->assignment->due_at)->take(3),
        ]);
    }

    public function profile(Request $request)
    {
        return view('student.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->merge(['email' => strtolower(trim((string) $request->email))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'regex:/^[^@\s]+@gmail\.com$/i', 'max:255', Rule::unique('users')->ignore($user->id)],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:Male,Female,Other,Prefer not to say'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);
        $oldPhoto = $user->profile_photo_path;
        $newPhoto = null;
        unset($data['photo'], $data['remove_photo']);
        if ($request->hasFile('photo')) {
            $newPhoto = $request->file('photo')->store('profile-photos', 'local');
            abort_unless($newPhoto, 500, 'Unable to store your photo. Please try again.');
            $data['profile_photo_path'] = $newPhoto;
        } elseif ($request->boolean('remove_photo')) {
            $data['profile_photo_path'] = null;
        }
        $emailChanged = $data['email'] !== $user->email;
        try {
            $user->fill($data);
            if ($emailChanged) { $user->email_verified_at = null; }
            $user->save();
        } catch (\Throwable $e) {
            if ($newPhoto) { Storage::disk('local')->delete($newPhoto); }
            throw $e;
        }
        if ($oldPhoto && $oldPhoto !== $user->profile_photo_path) { Storage::disk('local')->delete($oldPhoto); }
        if ($emailChanged) {
            Cache::forget('email-otp:'.$user->id);
            Cache::forget('email-otp:'.$user->id.':cooldown');
            try { $user->sendEmailVerificationNotification(); }
            catch (\Throwable $e) {
                report($e);
                return redirect()->route('verification.notice')->withErrors(['otp' => 'Profile saved. Email could not be sent; please use Resend OTP.']);
            }
            return redirect()->route('verification.notice')->with('status', 'Profile saved. Verify your new Gmail address with the OTP we sent.');
        }
        return redirect()->route('student.profile')->with('status', 'Your profile has been updated.');
    }

    public function photo(Request $request)
    {
        $path = $request->user()->profile_photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            'theme' => ['required', 'in:light,dark,system'],
            'density' => ['required', 'in:comfortable,compact'],
            'text_size' => ['required', 'in:standard,large'],
            'reduce_motion' => ['required', 'boolean'],
        ]);
        $data['reduce_motion'] = $request->boolean('reduce_motion');
        $request->user()->update(['preferences' => $data]);
        return back()->with('status', 'Your appearance preferences have been saved.');
    }

    public function enrollment(Request $request)
    {
        $user = $request->user();
        $enrolledIds = $user->enrollments()->where('semester', self::SEMESTER)->pluck('subject_id');

        return view('student.enrollment', [
            'enrolled' => Subject::whereIn('id', $enrolledIds)->get(),
            'available' => Subject::whereNotIn('id', $enrolledIds)->orderBy('code')->get(),
            'semester' => self::SEMESTER,
        ]);
    }

    public function enroll(Request $request, Subject $subject)
    {
        Enrollment::firstOrCreate([
            'user_id' => $request->user()->id, 'subject_id' => $subject->id, 'semester' => self::SEMESTER,
        ]);

        return back()->with('status', "Enrolled in {$subject->code}.");
    }

    public function drop(Request $request, Subject $subject)
    {
        $request->user()->enrollments()
            ->where('subject_id', $subject->id)->where('semester', self::SEMESTER)->whereNull('grade')->delete();

        return back()->with('status', "Dropped {$subject->code} (graded subjects cannot be dropped).");
    }

    public function subjects(Request $request)
    {
        return view('student.subjects', [
            'enrollments' => $request->user()->enrollments()->with('subject.teacher')->get(),
        ]);
    }

    public function grades(Request $request)
    {
        $user = $request->user();

        return view('student.grades', [
            'enrollments' => $user->enrollments()->with('subject')->whereNotNull('grade')->get(),
            'gpa' => $user->gpa(),
            'semester' => self::SEMESTER,
        ]);
    }

    public function schedule(Request $request)
    {
        return view('student.schedule', [
            'enrollments' => $request->user()->enrollments()->with('subject')->where('semester', self::SEMESTER)->get(),
        ]);
    }

    public function announcements(Request $request)
    {
        return view('student.announcements', ['announcements' => Announcement::published()->visibleTo($request->user())->with('subject')->paginate(8)]);
    }

    public function settings()
    {
        return view('student.settings', ['user' => request()->user()]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->update(['password' => $request->password]);

        return back()->with('status', 'Password changed successfully.');
    }
}