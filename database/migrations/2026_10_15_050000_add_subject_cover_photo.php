<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('subjects', fn(Blueprint $table) => $table->string('cover_photo_path')->nullable()); }
    public function down(): void { Schema::table('subjects', fn(Blueprint $table) => $table->dropColumn('cover_photo_path')); }
};
