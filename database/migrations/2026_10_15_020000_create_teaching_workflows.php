<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('subjects', function(Blueprint $t) { $t->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete(); });
        Schema::table('announcements', function(Blueprint $t) { $t->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete(); });
        Schema::create('assignments', function(Blueprint $t) {
            $t->id(); $t->foreignId('subject_id')->constrained()->cascadeOnDelete(); $t->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $t->string('title'); $t->text('instructions'); $t->string('semester'); $t->dateTime('due_at'); $t->unsignedInteger('points')->default(100); $t->timestamps();
        });
        Schema::create('submissions', function(Blueprint $t) {
            $t->id(); $t->foreignId('assignment_id')->constrained()->cascadeOnDelete(); $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->text('answer')->nullable(); $t->dateTime('submitted_at')->nullable(); $t->decimal('score',8,2)->nullable(); $t->text('feedback')->nullable(); $t->dateTime('graded_at')->nullable(); $t->timestamps(); $t->unique(['assignment_id','student_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('submissions'); Schema::dropIfExists('assignments');
        Schema::table('announcements', fn(Blueprint $t) => $t->dropConstrainedForeignId('teacher_id'));
        Schema::table('subjects', fn(Blueprint $t) => $t->dropConstrainedForeignId('teacher_id'));
    }
};
