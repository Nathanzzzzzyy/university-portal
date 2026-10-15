<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Teacher account (already email-verified) ----
        User::updateOrCreate(['email' => 'teacher@gmail.com'], [
            'name' => 'Teacher User', 'username' => 'teacher', 'password' => 'Teacher@12345',
            'role' => 'teacher', 'status' => 'active', 'email_verified_at' => now(),
        ]);

        // ---- Subjects ----
        $subjects = [
            ['IT101', 'Introduction to IT', 3, 'MWF', '8:00 - 9:00 AM', 'CL-201'],
            ['IT102', 'Computer Programming 1', 3, 'TTh', '9:00 - 10:30 AM', 'CL-Lab 1'],
            ['MATH101', 'College Algebra', 3, 'MWF', '10:00 - 11:00 AM', 'RM-305'],
            ['NSTP1', 'NSTP', 3, 'Sat', '8:00 - 11:00 AM', 'Gym'],
            ['PE1', 'Physical Education', 2, 'Fri', '1:00 - 3:00 PM', 'Gym'],
            ['ENG101', 'Purposive Communication', 3, 'TTh', '1:00 - 2:30 PM', 'RM-210'],
            ['HIST101', 'Readings in Philippine History', 3, 'MWF', '2:00 - 3:00 PM', 'RM-112'],
        ];
        foreach ($subjects as [$code, $title, $units, $day, $time, $room]) {
            Subject::updateOrCreate(['code' => $code], [
                'title' => $title, 'units' => $units,
                'schedule_day' => $day, 'schedule_time' => $time, 'room' => $room,
            ]);
        }

        // ---- Demo student (verified, so you can log in immediately) ----
        $juan = User::updateOrCreate(['email' => 'juan@gmail.com'], [
            'name' => 'Juan Dela Cruz', 'password' => 'Student@12345', 'role' => 'student',
            'status' => 'active', 'student_number' => '2025001234', 'course' => 'BSIT',
            'year_level' => '1st Year', 'birthdate' => '2006-01-01', 'gender' => 'Male',
            'address' => 'Baguio City, Benguet', 'contact_number' => '09123456789',
            'email_verified_at' => now(),
        ]);
        $grades = ['IT101' => 1.75, 'IT102' => 1.50, 'MATH101' => 2.00, 'NSTP1' => 1.75, 'PE1' => 1.25];
        foreach ($grades as $code => $grade) {
            Enrollment::updateOrCreate(
                ['user_id' => $juan->id, 'subject_id' => Subject::where('code', $code)->first()->id, 'semester' => '1st Semester 2025 - 2026'],
                ['grade' => $grade]
            );
        }

        // ---- Announcements ----
        $news = [
            ['Enrollment for 1st Semester 2025-2026', 'Enrollment is now open for all programs. Visit the Registrar or enroll online.'],
            ['Class Suspension (August 20, 2025)', 'Classes are suspended due to inclement weather. Stay safe, Cordillerans!'],
            ['Library Extended Hours', 'The library will be open until 8:00 PM during midterm week.'],
            ['NSTP Orientation', 'All NSTP students are required to attend the orientation at the gym.'],
        ];
        foreach ($news as $i => [$title, $content]) {
            Announcement::updateOrCreate(['title' => $title], [
                'content' => $content, 'status' => 'published', 'published_at' => now()->subDays($i * 2),
            ]);
        }
    }
}