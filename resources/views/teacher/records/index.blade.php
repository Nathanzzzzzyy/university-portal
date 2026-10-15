@extends('layouts.app')
@section('title', 'Gradebook')

@section('content')
<div class="page-intro"><div><span class="eyebrow">SUPPORT EVERY LEARNER</span><h2>Progress deserves attention.</h2><p>View students in your classes and post official subject grades.</p></div><a class="btn ghost" href="{{ route('teacher.classes.index') }}">My classes ?</a></div>
<form class="toolbar" method="GET">
    <input class="grow" name="q" value="{{ request('q') }}" placeholder="Search student name or number...">
    <button class="btn ghost">Search</button>
</form>
<div class="card">
    <div class="table-wrap"><table class="tbl">
        <tr><th>Student No.</th><th>Name</th><th>Course</th><th>Subjects</th><th></th></tr>
        @forelse ($students as $s)
            <tr>
                <td>{{ $s->student_number }}</td><td>{{ $s->name }}</td><td>{{ $s->course }}</td>
                <td>{{ $s->enrollments_count }}</td><td>{{ $s->gpa() ?? '—' }}</td>
                <td><a class="btn sm" href="{{ route('teacher.records.show', $s) }}">Open record</a></td>
            </tr>
        @empty<tr><td colspan="5"><div class="empty-state"><h3>No students to show yet</h3><p>Add students to your classes, or try a different search.</p><a class="btn" href="{{ route('teacher.classes.index') }}">Manage classes ?</a></div></td></tr>@endforelse
    </table></div>
    {{ $students->links() }}
</div>
@endsection