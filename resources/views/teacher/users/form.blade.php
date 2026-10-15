@extends('layouts.app')

@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')

<div class="user-form-page">

```
<div class="form-header">
    <div>
        <span class="form-eyebrow">ADMINISTRATION</span>
        <h2>{{ $user->exists ? 'Edit User' : 'Add New User' }}</h2>
        <p>
            {{ $user->exists
                ? 'Update the information and account settings of this user.'
                : 'Create a new student or teacher account.' }}
        </p>
    </div>

    <a class="btn ghost" href="{{ route('teacher.users.index') }}">
        ← Back to Users
    </a>
</div>

<div class="user-form-card">

    <form method="POST"
          action="{{ $user->exists
                ? route('teacher.users.update', $user)
                : route('teacher.users.store') }}">

        @csrf

        @if ($user->exists)
            @method('PUT')
        @endif

        {{-- PERSONAL INFORMATION --}}
        <div class="form-section">
            <div class="section-title">
                <div class="section-icon">👤</div>
                <div>
                    <h3>Personal Information</h3>
                    <p>Basic information about the user.</p>
                </div>
            </div>

            <div class="field">
                <label for="name">Full Name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    placeholder="Enter full name"
                    required
                >

                @error('name')
                    <div class="err">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">

                <div class="field">
                    <label for="email">Email Address</label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        placeholder="example@email.com"
                        required
                    >

                    @error('email')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="username">Username</label>

                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username', $user->username) }}"
                        placeholder="For teachers"
                    >

                    <small class="field-help">
                        Mainly used for teacher accounts.
                    </small>

                    @error('username')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>


        {{-- STUDENT INFORMATION --}}
        <div class="form-section">

            <div class="section-title">
                <div class="section-icon">🎓</div>
                <div>
                    <h3>Student Information</h3>
                    <p>Academic information for the user.</p>
                </div>
            </div>

            <div class="row">

                <div class="field">
                    <label for="student_number">Student Number</label>

                    <input
                        id="student_number"
                        type="text"
                        value="{{ $user->student_number ?: 'Auto-generated when saved' }}"
                        disabled
                    >

                    <small class="field-help">
                        Student numbers are automatically generated for students.
                    </small>
                </div>

                <div class="field">
                    <label for="course">Course</label>

                    <select id="course" name="course">

                        <option value="">Select Course</option>

                        @foreach ($courses as $c)

                            <option
                                value="{{ $c }}"
                                @selected(old('course', $user->course) === $c)
                            >
                                {{ $c }}
                            </option>

                        @endforeach

                    </select>

                    @error('course')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div class="field">

                <label for="year_level">Year Level</label>

                <select id="year_level" name="year_level">

                    <option value="">Select Year Level</option>

                    @foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $y)

                        <option
                            value="{{ $y }}"
                            @selected(old('year_level', $user->year_level) === $y)
                        >
                            {{ $y }}
                        </option>

                    @endforeach

                </select>

                @error('year_level')
                    <div class="err">{{ $message }}</div>
                @enderror

            </div>

        </div>


        {{-- ACCOUNT INFORMATION --}}
        <div class="form-section">

            <div class="section-title">
                <div class="section-icon">🔐</div>

                <div>
                    <h3>Account Settings</h3>
                    <p>Login credentials and account permissions.</p>
                </div>
            </div>

            <div class="row">

                <div class="field">

                    <label for="password">
                        Password
                        @if ($user->exists)
                            <span class="optional">(leave blank to keep current)</span>
                        @endif
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="{{ $user->exists ? 'Leave blank to keep current password' : 'Enter password' }}"
                        {{ $user->exists ? '' : 'required' }}
                    >

                    @error('password')
                        <div class="err">{{ $message }}</div>
                    @enderror

                </div>

                <div class="field">

                    <label for="role">Account Role</label>

                    <select id="role" name="role">

                        <option
                            value="student"
                            @selected(old('role', $user->role) === 'student')
                        >
                            Student
                        </option>

                        <option
                            value="teacher"
                            @selected(old('role', $user->role) === 'teacher')
                        >
                            Teacher
                        </option>

                    </select>

                    @error('role')
                        <div class="err">{{ $message }}</div>
                    @enderror

                </div>

            </div>

            <div class="field">

                <label for="status">Account Status</label>

                <select id="status" name="status">

                    <option
                        value="active"
                        @selected(old('status', $user->status) === 'active')
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        @selected(old('status', $user->status) === 'inactive')
                    >
                        Inactive
                    </option>

                </select>

                @error('status')
                    <div class="err">{{ $message }}</div>
                @enderror

            </div>

        </div>


        {{-- FORM ACTIONS --}}
        <div class="form-actions">

            <a
                class="btn ghost"
                href="{{ route('teacher.users.index') }}"
            >
                Cancel
            </a>

            <button type="submit" class="btn">
                {{ $user->exists ? '✓ Update User' : '+ Create User' }}
            </button>

        </div>

    </form>

</div>
```

</div>

@endsection
