<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Attachment extends Model {
    protected $fillable = ['assignment_id', 'submission_id', 'path', 'name', 'mime', 'size'];
    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function submission() { return $this->belongsTo(Submission::class); }
}
