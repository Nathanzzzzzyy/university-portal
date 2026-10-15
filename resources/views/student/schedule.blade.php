@extends('layouts.app')
@section('title', 'Schedule')

@section('content')
<div class="page-intro"><div><span class="eyebrow">PLAN YOUR WEEK</span><h2>A little structure. More focus.</h2><p>Your enrolled classes, meeting times, and rooms in one place.</p></div></div>
<div class="sched">
    @forelse ($enrollments as $e)
        <div class="slot">
            <span class="badge">{{ $e->subject->schedule_day ?? 'TBA' }}</span>
            <h3 style="margin:8px 0 2px;font-size:15px">{{ $e->subject->code }}</h3>
            <div>{{ $e->subject->title }}</div>
            <small style="color:var(--muted)">{{ $e->subject->schedule_time ?? 'TBA' }} &middot; {{ $e->subject->room ?? 'TBA' }}</small>
        </div>
    @empty
        <div class="card empty-state full-span"><span class="empty-symbol" aria-hidden="true">?</span><h3>A fresh week is waiting</h3><p>Your class schedule will appear here once you enroll in subjects.</p><a class="btn" href="{{ route('student.enrollment') }}">Explore subjects ?</a></div>
    @endforelse
</div>
@endsection