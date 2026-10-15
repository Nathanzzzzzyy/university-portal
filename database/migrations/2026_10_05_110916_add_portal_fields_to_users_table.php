<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('password');       // student | admin
            $table->string('status')->default('active')->after('role');          // active | inactive
            $table->string('username')->nullable()->unique()->after('email');   // admin login name
            $table->string('student_number')->nullable()->unique()->after('username');
            $table->string('course')->nullable();
            $table->string('year_level')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'username', 'student_number', 'course',
                'year_level', 'birthdate', 'gender', 'address', 'contact_number']);
        });
    }
};