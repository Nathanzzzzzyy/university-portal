<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Assignment extends Model {
    protected $fillable = ['subject_id','teacher_id','title','instructions','semester','due_at','points'];
    protected function casts(): array { return ['due_at' => 'datetime']; }
    public function attachments() { return $this->hasMany(Attachment::class)->whereNull('submission_id'); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function submissions() { return $this->hasMany(Submission::class); }
}
