<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


## Portal signup and verification

Student and teacher accounts register at `/register` using a personal `@gmail.com` address.
Student numbers are automatic; teachers do not need a course or student number.
Both roles must enter the emailed four-digit OTP before accessing their dashboard.
Codes expire after 10 minutes, resend is limited to once per minute, and verification
allows five failed attempts per 10 minutes. Teacher login is `/teacher/login`.
The teacher portal replaces the previous admin portal and retains its management features.
Run `php artisan migrate` to migrate existing admin roles to teacher without deleting accounts.

Configure `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`,
`MAIL_SCHEME=smtp`, `MAIL_USERNAME` (sender Gmail), `MAIL_PASSWORD` (sender app password),
and `MAIL_FROM_ADDRESS` (sender Gmail) in `.env`. Keep credentials out of source control.
After mail changes run `php artisan config:clear`. Mail delivery errors preserve the
account and allow retrying through Resend OTP. Automated tests use an in-memory mailer;
they do not establish delivery to a real Gmail inbox.


## Teacher workflow

`/teacher` is a teaching workspace, replacing the former admin dashboard.
Under **My Classes**, add an unassigned catalog subject or create a new subject.
Only one teacher owns a subject. Add active students to its current-semester roster;
this creates their enrollment and updates their subjects and schedule.
Under **Assignments**, publish work to all active students currently in that class,
or selected enrolled students. Recipient lists are snapshots at publication; students
added later do not receive old tasks automatically. Students open **Classwork** to
submit written answers, PDF/DOCX files, or pictures. Late work is accepted
and labelled. Teachers review, score and return feedback; returned work is locked
for student editing. Assignment points do not automatically calculate official
subject grades: enter those separately in **Gradebook** using the 1.00?5.00 scale.
Teachers can access only their own classwork, student class records, and announcements.
The old user-management routes have been removed from teacher access.
Existing subjects remain unassigned until explicitly added to a teacher's classes.
Deadlines display in `APP_TIMEZONE` (default `Asia/Manila`).

Workflow references: [Google Classroom assignment creation](https://support.google.com/edu/classroom/answer/6020265?hl=en)
and [grading and returning work](https://support.google.com/edu/classroom/answer/6020294?hl=en).


Teacher announcements require selecting one of the teacher's own classes. Only
students enrolled in that class in the announcement's semester can see the post,
on both the student dashboard noticeboard and Announcements page. Drafts remain
hidden. Existing teacher posts without a class are hidden until the teacher edits
and assigns them to a class; legacy campus-wide notices remain campus-wide.


Assignments support optional teacher learning-material attachments and student file
submissions: PDF, DOCX, JPG/JPEG, PNG, WebP; at most five files, 5 MB per file.
Students may submit files without a written answer. Uploading new submission files
replaces all previous files; submitting without new files keeps existing uploads.
Returned work remains locked. Files are stored privately and served as downloads
only to the assignment teacher and its recipients; students cannot download a peer's
submission. Select files again after form validation errors. The PHP upload/post
limits must accommodate these sizes (up to 25 MB total plus form overhead).


Email verification uses four separate single-digit boxes, numeric input, paste and
one-time-code autofill support, and a server-backed resend countdown. Four-digit
codes preserve leading zeros, expire after 10 minutes, and allow five wrong attempts
per 10 minutes; resend does not reset the attempt limit. Old six-digit emails require
a new code through Resend code. Authentication validates both student and teacher
roles, Gmail signup, required student course, password confirmation, account status,
and verified-email access. Run `node --test tests/Frontend/otp.test.cjs` for the OTP
input interaction checks and `php vendor/phpunit/phpunit/phpunit --do-not-cache-result`
for backend tests.


Teachers can upload subject cover photos when creating a subject or through
My Classes ? Open class ? Class cover. JPG, PNG and WebP images up to 5 MB and
6000 ? 6000 pixels are accepted. Replace or remove a cover without changing class
details. The same image appears on teacher class cards and student subject cards,
and in dashboard class thumbnails. Cover files are private to the class teacher
and enrolled students; replacement/removal cleans up the previous file.
#   u n i v e r s i t y - p o r t a l  
 #   u n i v e r s i t y - p o r t a l  
 #   u n i v e r s i t y - p o r t a l  
 #   u n i v e r s i t y - p o r t a l  
 