<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Submission extends Model {
    protected $fillable = ['assignment_id','student_id','answer','submitted_at','score','feedback','graded_at'];
    protected function casts(): array { return ['submitted_at'=>'datetime','graded_at'=>'datetime','score'=>'decimal:2']; }
    public function attachments() { return $this->hasMany(Attachment::class); }
    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function status(): string {
        if ($this->graded_at) return 'Returned';
        if ($this->submitted_at) return $this->submitted_at->gt($this->assignment->due_at) ? 'Submitted late' : 'Submitted';
        return $this->assignment->due_at->isPast() ? 'Missing' : 'Assigned';
    }
}
