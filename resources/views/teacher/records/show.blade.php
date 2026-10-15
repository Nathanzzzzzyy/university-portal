@extends('layouts.app')
@section('title', 'Student Record')

@section('content')
<div class="page-intro"><div><span class="eyebrow">OFFICIAL SUBJECT GRADES</span><h2>A clearer picture of progress.</h2><p>Only your classes are shown. Final grades use the 1.00?5.00 scale; assignment scores remain in Classwork.</p></div></div>
<div class="card">
    <h3>{{ $student->name }} <small style="color:var(--muted);font-weight:400">{{ $student->student_number }} &middot; {{ $student->course }} &middot; GPA {{ $student->gpa() ?? '—' }}</small></h3>
    <div class="table-wrap"><table class="tbl">
        <tr><th>Code</th><th>Title</th><th>Units</th><th>Semester</th><th>Grade (1.00 - 5.00)</th></tr>
        @forelse ($enrollments as $e)
            <tr>
                <td>{{ $e->subject->code }}</td><td>{{ $e->subject->title }}</td><td>{{ $e->subject->units }}</td><td>{{ $e->semester }}</td>
                <td>
                    <form method="POST" action="{{ route('teacher.records.grade', $e) }}" style="display:flex;gap:6px">
                        @csrf @method('PUT')
                        <input type="number" step="0.01" min="1" max="5" aria-label="Final grade for {{ $e->subject->code }}" name="grade" value="{{ $e->grade }}" style="width:90px;padding:6px 10px;border:1px solid var(--line);border-radius:8px">
                        <button class="btn sm">Save</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" style="color:var(--muted)">This student has no enrollments.</td></tr>
        @endforelse
    </table></div>
    <a class="btn ghost" href="{{ route('teacher.records.index') }}" style="margin-top:14px">&larr; Back</a>
</div>
@endsection