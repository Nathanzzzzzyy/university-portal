@extends('layouts.app')
@section('title', 'Announcements')

@section('content')
<div class="page-intro"><div><span class="eyebrow">STAY IN THE LOOP</span><h2>The campus noticeboard.</h2><p>Updates and important messages from your university.</p></div></div>
@forelse ($announcements as $a)
    <div class="card">
        <span class="eyebrow">{{ $a->subject ? $a->subject->code.' ? '.$a->semester : 'Campus announcement' }}</span>
        <h3>{{ $a->title }} <small style="color:var(--muted);font-weight:400">{{ $a->published_at?->format('F d, Y') }}</small></h3>
        <p style="white-space:pre-line;line-height:1.9;font-size:12px">{{ $a->content }}</p>
    </div>
@empty
    <div class="card empty-state"><span class="empty-symbol" aria-hidden="true">?</span><h3>You?re all caught up</h3><p>There are no campus announcements right now. Check back for new updates.</p><a class="btn ghost" href="{{ route('student.dashboard') }}">Back to overview</a></div>
@endforelse
{{ $announcements->links() }}
@endsection