<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isTeacher()) {
            return redirect()->route('teacher.login')
                ->withErrors(['username' => 'Teacher access only.']);
        }

        return $next($request);
    }
}