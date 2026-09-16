<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function switchRole(Request $request)
    {
        $user = $request->user();

        // GUNAKAN getRawOriginal('role') untuk mengambil data asli di database
        // Ini lebih aman daripada mengakses properti protected
        if ($user->hasRole(UserRole::ASLAB)) {

            // Baca role yang sedang aktif sekarang
            $currentActive = $user->active_role;

            // Tukar mode
            $newRole = $user->hasActiveRole(UserRole::ASLAB)
                ? UserRole::MAHASISWA->value
                : UserRole::ASLAB->value;

            // Simpan ke Session
            session(['active_role' => $newRole]);

            return back()->with('success', 'Mode tampilan diubah menjadi: '.$newRole);
        }

        return back()->with('error', 'Anda tidak memiliki akses untuk fitur ini.');
    }
}
