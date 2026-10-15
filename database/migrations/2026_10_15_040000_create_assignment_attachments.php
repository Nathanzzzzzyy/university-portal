<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('path'); $table->string('name'); $table->string('mime');
            $table->unsignedBigInteger('size'); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('attachments'); }
};
