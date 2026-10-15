@extends('layouts.app')
@section('title', 'Enrollment')

@section('content')
<div class="page-intro"><div><span class="eyebrow">BUILD YOUR SEMESTER</span><h2>Make room for what?s next.</h2><p>Choose your classes and keep your academic load organized.</p></div></div>
<p style="color:var(--muted);margin-bottom:16px">Semester: <b>{{ $semester }}</b></p>

<div class="grid2">
    <div class="card">
        <h3>Enrolled Subjects ({{ $enrolled->count() }})</h3>
        <div class="table-wrap"><table class="tbl">
            <tr><th>Code</th><th>Title</th><th>Units</th><th></th></tr>
            @forelse ($enrolled as $s)
                <tr><td>{{ $s->code }}</td><td>{{ $s->title }}</td><td>{{ $s->units }}</td>
                    <td><form method="POST" action="{{ route('student.drop', $s) }}" onsubmit="return confirm('Drop {{ $s->code }}?')">@csrf @method('DELETE')<button class="btn danger sm">Drop</button></form></td></tr>
            @empty
                <tr><td colspan="4" style="color:var(--muted)">Nothing enrolled yet.</td></tr>
            @endforelse
        </table></div>
    </div>

    <div class="card">
        <h3>Available Subjects</h3>
        <div class="table-wrap"><table class="tbl">
            <tr><th>Code</th><th>Title</th><th>Units</th><th></th></tr>
            @forelse ($available as $s)
                <tr><td>{{ $s->code }}</td><td>{{ $s->title }}</td><td>{{ $s->units }}</td>
                    <td><form method="POST" action="{{ route('student.enroll', $s) }}">@csrf<button class="btn sm">Enroll</button></form></td></tr>
            @empty
                <tr><td colspan="4" style="color:var(--muted)">You are enrolled in every available subject.</td></tr>
            @endforelse
        </table></div>
    </div>
</div>
@endsection