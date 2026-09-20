<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccountApproved
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()->approved_at) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Akun menunggu verifikasi Laboran atau Aslab.'], 403)
                : redirect()->route('account.pending');
        }

        return $next($request);
    }
}
