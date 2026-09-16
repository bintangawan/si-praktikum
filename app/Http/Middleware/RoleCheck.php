<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheck
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Pastikan user sudah login
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $activeRole = UserRole::normalize($request->user()->active_role);
        $allowedRoles = array_filter(array_map(UserRole::normalize(...), $roles));

        // Cek apakah role user ada dalam daftar yang diizinkan
        if (! $activeRole || ! in_array($activeRole, $allowedRoles, true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        return $next($request);
    }
}
