<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountApprovalController extends Controller
{
    public function pending(Request $request)
    {
        return $request->user()->approved_at
            ? redirect()->route('dashboard')
            : view('auth.account-pending');
    }

    public function index()
    {
        return view('accounts.approvals', [
            'students' => User::where('role', 'Mahasiswa')->whereNull('approved_at')->orderBy('created_at')->get(),
            'pendingCount' => User::where('role', 'Mahasiswa')->whereNull('approved_at')->count(),
        ]);
    }

    public function approve(Request $request, User $user)
    {
        abort_unless($user->hasRole('Mahasiswa'), 403);
        User::whereKey($user->id)->whereNull('approved_at')->update([
            'approved_at' => now(), 'approved_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Akun mahasiswa telah diverifikasi.');
    }

    public function approveAll(Request $request)
    {
        $count = DB::transaction(fn () => User::where('role', 'Mahasiswa')->whereNull('approved_at')->update([
            'approved_at' => now(), 'approved_by' => $request->user()->id,
        ]));

        return back()->with('success', "{$count} akun mahasiswa berhasil diverifikasi.");
    }
}
