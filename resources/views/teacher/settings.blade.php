@extends('layouts.app')
@section('title', 'Settings')

@section('content')
<div class="page-intro"><div><span class="eyebrow">YOUR TEACHING ACCOUNT</span><h2>A workspace you can count on.</h2><p>Manage your subject catalog and keep your account secure.</p></div><a class="btn ghost" href="{{ route('teacher.classes.index') }}">Classes & rosters ?</a></div>
<div class="grid2">
    <div class="card">
        <h3>Subjects ({{ $subjects->count() }})</h3>
        <form method="POST" action="{{ route('teacher.subjects.store') }}" class="subject-create-form" enctype="multipart/form-data">
@csrf
@include('partials.subject-fields')
<button class="btn" type="submit">Add subject</button>
</form>
        <div class="table-wrap"><table class="tbl">
            @foreach ($subjects as $s)
                <tr><td>{{ $s->code }}</td><td>{{ $s->title }}</td><td>{{ $s->units }}u</td>
                    <td><form method="POST" action="{{ route('teacher.subjects.destroy', $s) }}" onsubmit="return confirm('Delete {{ $s->code }}?')">@csrf @method('DELETE')<button class="btn danger sm" aria-label="Delete {{ $s->code }}">Delete</button></form></td></tr>
            @endforeach
        </table></div>
    </div>

    <div class="card">
        <h3>Change Password</h3>
        <form method="POST" action="{{ route('teacher.password') }}">
            @csrf @method('PUT')
            <div class="field"><label for="current-password">Current password</label><div class="password-control"><input id="current-password" type="password" name="current_password" autocomplete="current-password" maxlength="72" required><button type="button" data-toggle-password="current-password" aria-label="Show current password" aria-pressed="false">Show</button></div>@error('current_password')<p class="err">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="new-password">New password</label><div class="password-control"><input id="new-password" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" required><button type="button" data-toggle-password="new-password" aria-label="Show new password" aria-pressed="false">Show</button></div><small class="field-help">8?72 characters with letters and numbers. Must differ from your current password.</small>@error('password')<p class="err">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="confirm-password">Confirm new password</label><div class="password-control"><input id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" required><button type="button" data-toggle-password="confirm-password" aria-label="Show confirmation password" aria-pressed="false">Show</button></div></div>
            <button class="btn">Update password</button>
        </form>
    </div>
</div>
@endsection