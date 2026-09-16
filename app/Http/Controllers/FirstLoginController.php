<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class FirstLoginController extends Controller
{
    public function showChangePasswordForm()
    {
        // Jika ternyata sudah pernah ganti password, lempar ke dashboard
        if (! Auth::user()->is_first_login) {
            return redirect()->route('dashboard');
        }

        return view('auth.first-login-change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal harus 8 karakter.',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->is_first_login = false; // Tandai sudah bukan login pertama lagi
        $user->save();

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Password berhasil diperbarui.');
    }
}
