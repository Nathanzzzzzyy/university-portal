@extends('layouts.app')
@section('title', $item->exists ? 'Edit Announcement' : 'Add Announcement')

@section('content')
<div class="page-intro"><div><span class="eyebrow">KEEP YOUR CLASS CONNECTED</span><h2>A message for your learners.</h2><p>Only students enrolled in the selected class and semester can see this announcement.</p></div></div>
@if($classes->isEmpty())<div class="alert">Add a class before publishing an announcement. <a href="{{ route('teacher.classes.index') }}">Manage my classes ?</a></div>@endif
<div class="card" style="max-width:640px">
    <form method="POST" action="{{ $item->exists ? route('teacher.announcements.update', $item) : route('teacher.announcements.store') }}">
        @csrf
        @if ($item->exists) @method('PUT') @endif
        <div class="field"><label for="announcement-class">Class *</label>
            <select id="announcement-class" name="subject_id" required>
                <option value="">Select your class?</option>
                @foreach($classes as $class)<option value="{{ $class->id }}" @selected((string) old('subject_id', $item->subject_id) === (string) $class->id)>{{ $class->code }} ? {{ $class->title }}</option>@endforeach
            </select>
            <small class="field-help">{{ \App\Http\Controllers\StudentController::SEMESTER }} ? Visible to this class only.</small>
            @error('subject_id')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div class="field"><label>Title</label><input name="title" value="{{ old('title', $item->title) }}" required></div>
        <div class="field"><label>Content</label><textarea name="content" rows="6" required>{{ old('content', $item->content) }}</textarea></div>
        <div class="field"><label>Status</label>
            <select name="status"><option value="published" @selected(old('status', $item->status) === 'published')>Published</option><option value="draft" @selected(old('status', $item->status) === 'draft')>Draft</option></select>
        </div>
        <button class="btn" @disabled($classes->isEmpty())>Save announcement</button>
        <a class="btn ghost" href="{{ route('teacher.announcements.index') }}">Cancel</a>
    </form>
</div>
@endsection