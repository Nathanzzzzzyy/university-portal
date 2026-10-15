<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('student_number', 'like', "%$s%")))
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('id')->paginate(10)->withQueryString();

        return view('teacher.users.index', compact('users'));
    }

    public function create()
    {
        return view('teacher.users.form', ['user' => new User(['role' => 'student', 'status' => 'active']), 'courses' => RegisterController::COURSES]);
    }

    public function store(Request $request)
    {
               $data = $this->rules($request);
        $data['password'] = $request->validate(['password' => ['required', Password::min(8)->letters()->numbers()]])['password'];

        // students get the next Student Number automatically; teachers have none
        $user = $data['role'] === 'student' ? User::createStudent($data) : User::create($data);
        $user->forceFill(['email_verified_at' => now()])->save(); // teacher-created accounts are pre-verified

        $note = $user->student_number ? " Student Number: {$user->student_number}" : '';

        return redirect()->route('teacher.users.index')->with('status', "User created.{$note}");
    }

    public function edit(User $user)
    {
        return view('teacher.users.form', ['user' => $user, 'courses' => RegisterController::COURSES]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->rules($request, $user);
        if ($request->filled('password')) {
            $data['password'] = $request->validate(['password' => [Password::min(8)->letters()->numbers()]])['password'];
        }
        // never let the last teacher demote/deactivate themselves
        if ($user->id === $request->user()->id) {
            $data['role'] = 'teacher';
            $data['status'] = 'active';
        }
                // a user who becomes a student (and has no number yet) gets one
        if ($data['role'] === 'student' && empty($user->student_number)) {
            $data['student_number'] = User::nextStudentNumber();
        }
        $user->update($data);

        return redirect()->route('teacher.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }
        $user->delete();

        return back()->with('status', 'User deleted.');
    }

    private function rules(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
                      // teachers log in with a username, so it is required for them
            'username' => ['required_if:role,teacher', 'nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user?->id)],
            'student_number' => ['nullable', 'string', 'max:30', Rule::unique('users', 'student_number')->ignore($user?->id)],
            'course' => ['nullable', 'string', 'max:20'],
            'year_level' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:student,teacher'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}