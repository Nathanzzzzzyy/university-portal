@extends('layouts.app')
@section('title', 'Grades')

@section('content')
<div class="page-intro"><div><span class="eyebrow">YOUR ACADEMIC RECORD</span><h2>Every effort adds up.</h2><p>Review posted grades and track your academic progress.</p></div></div>
<div class="card">
    <div class="between">
        <div><b>{{ $semester }}</b></div>
        <button class="btn sm gold no-print" onclick="window.print()">Download PDF / Print</button>
    </div>
    <div class="table-wrap"><table class="tbl">
        <tr><th>Subject Code</th><th>Subject Title</th><th>Units</th><th>Grade</th></tr>
        @forelse ($enrollments as $e)
            <tr><td>{{ $e->subject->code }}</td><td>{{ $e->subject->title }}</td><td>{{ $e->subject->units }}</td><td><b>{{ number_format($e->grade, 2) }}</b></td></tr>
        @empty
            <tr><td colspan="4" style="color:var(--muted)">No grades have been posted yet.</td></tr>
        @endforelse
    </table></div>
    <div style="text-align:right"><span class="gpa-box">GPA: {{ $gpa ? number_format($gpa, 2) : '—' }}</span></div>
</div>
@endsection