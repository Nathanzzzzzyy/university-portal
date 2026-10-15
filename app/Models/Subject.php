<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['cover_photo_path', 'teacher_id', 'code', 'title', 'units', 'schedule_day', 'schedule_time', 'room'];

    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}