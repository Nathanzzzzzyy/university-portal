@php
    $user = auth()->user();
    $ico = fn ($d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
    $icons = [
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5 7-5s7 1.5 7 5"/><path d="M16 4.5a3.5 3.5 0 010 7M18 15c2.5.5 4 2 4 5"/>',
        'book' => '<path d="M4 4h9a4 4 0 014 4v13H8a4 4 0 01-4-4z"/><path d="M4 17a4 4 0 014-4h9"/>',
        'edit' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/>',
        'chart' => '<path d="M4 20V4M4 20h16"/><path d="M8 16v-5M12 16V8M16 16v-8"/>',
        'cal' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'bell' => '<path d="M6 9a6 6 0 0112 0c0 6 2 7 2 7H4s2-1 2-7z"/><path d="M10 20a2 2 0 004 0"/>',
        'cog' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
        'out' => '<path d="M9 4H5a1 1 0 00-1 1v14a1 1 0 001 1h4M16 8l4 4-4 4M20 12H9"/>',
    ];
    $nav = $user->isTeacher()
        ? [
            ['teacher.dashboard', 'Dashboard', 'home', 'teacher.dashboard'],
            ['teacher.classes.index', 'My Classes', 'book', 'teacher.classes.*'],
            ['teacher.assignments.index', 'Assignments', 'edit', 'teacher.assignments.*'],
            ['teacher.records.index', 'Gradebook', 'chart', 'teacher.records.*'],
            ['teacher.announcements.index', 'Announcements', 'bell', 'teacher.announcements.*'],
            ['teacher.settings', 'Settings', 'cog', 'teacher.settings'],
        ]
        : [
            ['student.dashboard', 'Dashboard', 'home', 'student.dashboard'],
            ['student.profile', 'My Profile', 'user', 'student.profile'],
            ['student.enrollment', 'Enrollment', 'edit', 'student.enrollment'],
            ['student.subjects', 'Subjects', 'book', 'student.subjects'],
            ['student.classwork', 'Classwork', 'edit', 'student.classwork*'],
            ['student.grades', 'Grades', 'chart', 'student.grades'],
            ['student.schedule', 'Schedule', 'cal', 'student.schedule'],
            ['student.announcements', 'Announcements', 'bell', 'student.announcements'],
            ['student.settings', 'Settings', 'cog', 'student.settings'],
        ];
@endphp
<!DOCTYPE html>
<html lang="en" data-theme="{{ $user->preferences['theme'] ?? 'light' }}" data-density="{{ $user->preferences['density'] ?? 'comfortable' }}" data-text-size="{{ $user->preferences['text_size'] ?? 'standard' }}" data-reduce-motion="{{ ($user->preferences['reduce_motion'] ?? false) ? 'true' : 'false' }}">
<head>
    <meta charset="utf-8">
    <script>document.documentElement.dataset.resolvedTheme = document.documentElement.dataset.theme === 'system' ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : document.documentElement.dataset.theme;</script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | UC Portal</title>
    <link rel="icon" href="{{ asset('images/crest.png') }}">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student.css') }}">
    <link rel="stylesheet" href="{{ asset('css/teaching.css') }}">
    @stack('head')
</head>
<body class="{{ $user->isTeacher() ? 'teacher-portal student-portal' : 'student-portal' }}">
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="{{ asset('images/crest.png') }}" alt="UC">
            <span>UNIVERSITY OF THE<br>CORDILLERAS PANGASINAN</span>
        </div>
        <div class="who">
            <div class="avatar">@if($user->profile_photo_path)<img class="avatar-photo" src="{{ route('account.photo', ['v' => $user->updated_at?->timestamp]) }}" alt="{{ $user->name }}">@else{{ $user->initials() }}@endif</div>
            <div><b>{{ $user->name }}</b><small>{{ $user->isTeacher() ? 'Teacher' : ($user->course.' - '.$user->year_level) }}</small></div>
        </div>
        <nav aria-label="Main navigation">
            @foreach ($nav as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}" aria-current="{{ request()->routeIs($pattern) ? 'page' : 'false' }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">{!! $ico($icons[$icon]) !!} {{ $label }}</a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit">{!! $ico($icons['out']) !!} Logout</button>
        </form>
    </aside>

    <div class="main">
        <header class="topbar">
            <div style="display:flex;align-items:center">
                <button class="menu-btn" id="menu-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Open navigation">&#9776;</button>
                <h1>@yield('title')</h1>
            </div>
            <div class="right">
                <span>{{ $user->name }}</span>
                <div class="avatar" style="width:34px;height:34px;font-size:13px">@if($user->profile_photo_path)<img class="avatar-photo" src="{{ route('account.photo', ['v' => $user->updated_at?->timestamp]) }}" alt="{{ $user->name }}">@else{{ $user->initials() }}@endif</div>
            </div>
        </header>

        <main class="content" id="main-content" tabindex="-1">
            @if (session('status')) <div class="alert ok">{{ session('status') }}</div> @endif
            @if ($errors->any()) <div class="alert bad">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div> @endif
            @yield('content')
            @unless($user->isTeacher())<footer class="portal-footer"><span>UC Student Portal</span><span>Your learning journey, in one place.</span></footer>@endunless
        </main>
    </div>
</div>
<button class="nav-backdrop" id="nav-backdrop" aria-label="Close navigation" hidden></button>
<script src="{{ asset('js/portal.js') }}" defer></script>
@stack('scripts')
</body>
</html>