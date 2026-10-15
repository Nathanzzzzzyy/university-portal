<?php

use App\Http\Controllers\Teacher\TeacherController;
use App\Http\Controllers\Teacher\AnnouncementController;
use App\Http\Controllers\Teacher\RecordController;
use App\Http\Controllers\Teacher\SubjectController;
use App\Http\Controllers\Teacher\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::redirect('/admin', '/teacher');
Route::redirect('/admin/login', '/teacher/login');

// ---------- Guests ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showStudent'])->name('login');
    Route::post('/login', [LoginController::class, 'student'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/teacher/login', [LoginController::class, 'showTeacher'])->name('teacher.login');
    Route::post('/teacher/login', [LoginController::class, 'teacher'])->middleware('throttle:10,1');
});

Route::get('/subjects/{subject}/cover', [\App\Http\Controllers\SubjectCoverController::class, 'show'])->middleware(['auth', 'verified'])->name('subjects.cover');

Route::get('/attachments/{attachment}', [\App\Http\Controllers\AttachmentController::class, 'download'])->middleware(['auth', 'verified'])->name('attachments.download');

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ---------- Email verification ----------
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verify', [VerificationController::class, 'verify'])
        ->middleware('throttle:6,1')->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');

    Route::get('/account/photo', [StudentController::class, 'photo'])->name('account.photo');

    Route::get('/dashboard', [StudentController::class, 'redirect'])->name('dashboard');
});

// ---------- Student area (must be logged in AND email-verified) ----------
Route::middleware(['auth', 'verified', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
    Route::put('/profile', [StudentController::class, 'updateProfile'])->name('profile.update');
    Route::get('/enrollment', [StudentController::class, 'enrollment'])->name('enrollment');
    Route::post('/enrollment/{subject}', [StudentController::class, 'enroll'])->name('enroll');
    Route::delete('/enrollment/{subject}', [StudentController::class, 'drop'])->name('drop');
    Route::get('/classwork', [\App\Http\Controllers\ClassworkController::class, 'index'])->name('classwork');
    Route::put('/classwork/{submission}', [\App\Http\Controllers\ClassworkController::class, 'submit'])->name('classwork.submit');
    Route::get('/subjects', [StudentController::class, 'subjects'])->name('subjects');
    Route::get('/grades', [StudentController::class, 'grades'])->name('grades');
    Route::get('/schedule', [StudentController::class, 'schedule'])->name('schedule');
    Route::get('/announcements', [StudentController::class, 'announcements'])->name('announcements');
    Route::get('/settings', [StudentController::class, 'settings'])->name('settings');
    Route::put('/settings/preferences', [StudentController::class, 'updatePreferences'])->name('preferences');
    Route::put('/settings/password', [StudentController::class, 'updatePassword'])->name('password');
});

// ---------- Teacher area ----------
Route::middleware(['auth', 'verified', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [TeacherController::class, 'dashboard'])->name('dashboard');
    Route::get('/classes', [\App\Http\Controllers\Teacher\ClassroomController::class, 'index'])->name('classes.index');
    Route::get('/classes/{subject}', [\App\Http\Controllers\Teacher\ClassroomController::class, 'show'])->name('classes.show');
    Route::put('/classes/{subject}/cover', [\App\Http\Controllers\Teacher\ClassroomController::class, 'cover'])->name('classes.cover');
    Route::put('/classes/{subject}', [\App\Http\Controllers\Teacher\ClassroomController::class, 'update'])->name('classes.update');
    Route::post('/classes/{subject}/claim', [\App\Http\Controllers\Teacher\ClassroomController::class, 'claim'])->name('classes.claim');
    Route::post('/classes/{subject}/students', [\App\Http\Controllers\Teacher\ClassroomController::class, 'enroll'])->name('classes.enroll');
    Route::resource('assignments', \App\Http\Controllers\Teacher\AssignmentController::class)->only(['index','create','store','show']);
    Route::put('/submissions/{submission}/grade', [\App\Http\Controllers\Teacher\AssignmentController::class, 'grade'])->name('submissions.grade');
    Route::get('/records', [RecordController::class, 'index'])->name('records.index');
    Route::get('/records/{user}', [RecordController::class, 'show'])->name('records.show');
    Route::put('/records/grade/{enrollment}', [RecordController::class, 'grade'])->name('records.grade');
    Route::resource('announcements', AnnouncementController::class)->except('show');
    Route::get('/settings', [TeacherController::class, 'settings'])->name('settings');
    Route::put('/settings/password', [TeacherController::class, 'updatePassword'])->name('password');
    Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
});