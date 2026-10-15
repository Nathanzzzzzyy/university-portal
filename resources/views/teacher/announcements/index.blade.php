@extends('layouts.app')
@section('title', 'Announcements')

@section('content')
<div class="toolbar"><span class="grow"></span><a class="btn" href="{{ route('teacher.announcements.create') }}">+ Add Announcement</a></div>
<div class="card">
    <div class="table-wrap"><table class="tbl">
        <tr><th>Class</th><th>Title</th><th>Content</th><th>Date</th><th>Status</th><th>Action</th></tr>
        @foreach ($items as $a)
            <tr>
                <td><b>{{ $a->subject?->code ?: 'Choose a class' }}</b><small class="field-help">{{ $a->semester ?: 'Hidden from students until assigned' }}</small></td><td>{{ $a->title }}</td><td>{{ \Illuminate\Support\Str::limit($a->content, 50) }}</td>
                <td>{{ $a->published_at?->format('M d, Y') ?? '—' }}</td>
                <td><span class="badge {{ $a->status === 'published' ? '' : 'off' }}">{{ ucfirst($a->status) }}</span></td>
                <td style="display:flex;gap:6px">
                    <a class="btn ghost sm" href="{{ route('teacher.announcements.edit', $a) }}">Edit</a>
                    <form method="POST" action="{{ route('teacher.announcements.destroy', $a) }}" onsubmit="return confirm('Delete this announcement?')">@csrf @method('DELETE')<button class="btn danger sm">Delete</button></form>
                </td>
            </tr>
        @endforeach
    </table></div>
    {{ $items->links() }}
</div>
@endsection