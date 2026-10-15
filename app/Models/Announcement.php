<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['subject_id', 'semester', 'teacher_id', 'title', 'content', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function scopeVisibleTo($query, User $student)
    {
        return $query->where(function ($visibility) use ($student) {
            // Legacy campus notices remain campus-wide; unscoped teacher posts stay hidden.
            $visibility->where(fn ($campus) => $campus->whereNull('teacher_id')->whereNull('subject_id'))
                ->orWhere(function ($class) use ($student) {
                    $class->whereNotNull('teacher_id')->whereNotNull('subject_id')
                        ->whereHas('subject', fn ($subject) => $subject->whereColumn('subjects.teacher_id', 'announcements.teacher_id'))
                        ->whereExists(function ($enrollment) use ($student) {
                            $enrollment->selectRaw('1')->from('enrollments')
                                ->whereColumn('enrollments.subject_id', 'announcements.subject_id')
                                ->whereColumn('enrollments.semester', 'announcements.semester')
                                ->where('enrollments.user_id', $student->id);
                        });
                });
        });
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->latest('published_at');
    }
}