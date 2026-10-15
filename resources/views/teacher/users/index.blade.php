@extends('layouts.app')
@section('title', 'User Management')

@section('content')
<form class="toolbar" method="GET">
    <input class="grow" name="q" value="{{ request('q') }}" placeholder="Search by name, email or student number...">
    <select name="role"><option value="">All roles</option><option value="student" @selected(request('role')==='student')>Student</option><option value="teacher" @selected(request('role')==='teacher')>Teacher</option></select>
    <select name="status"><option value="">All status</option><option value="active" @selected(request('status')==='active')>Active</option><option value="inactive" @selected(request('status')==='inactive')>Inactive</option></select>
    <button class="btn ghost">Filter</button>
    <a class="btn" href="{{ route('teacher.users.create') }}">+ Add User</a>
</form>

<div class="card">
    <div class="table-wrap"><table class="tbl">
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Role</th><th>Verified</th><th>Status</th><th>Action</th></tr>
        @foreach ($users as $u)
            <tr>
                <td>{{ $u->id }}</td><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->course ?? '-' }}</td>
                <td>{{ ucfirst($u->role) }}</td>
                <td>{!! $u->email_verified_at ? '<span class="badge">Yes</span>' : '<span class="badge warn">No</span>' !!}</td>
                <td><span class="badge {{ $u->status === 'active' ? '' : 'off' }}">{{ ucfirst($u->status) }}</span></td>
                <td style="display:flex;gap:6px">
                    <a class="btn ghost sm" href="{{ route('teacher.users.edit', $u) }}">Edit</a>
                    <form method="POST" action="{{ route('teacher.users.destroy', $u) }}" onsubmit="return confirm('Delete {{ $u->name }}?')">@csrf @method('DELETE')<button class="btn danger sm">Delete</button></form>
                </td>
            </tr>
        @endforeach
    </table></div>
    {{ $users->links() }}
</div>
@endsection