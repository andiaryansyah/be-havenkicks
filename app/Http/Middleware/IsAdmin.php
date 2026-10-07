<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // sesuaikan dengan kolom di tabel users kamu
        // kalau pakai kolom `role` = 'admin'
        if (!$user || ($user->role !== 'admin' && !$user->is_admin)) {
             return response()->json(['message' => 'Forbidden. Admin only.'], 403);
        }

        return $next($request);
    }
}