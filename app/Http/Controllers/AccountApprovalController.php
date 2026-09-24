<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
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

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->hasRole('Mahasiswa'), 403);

        $deleted = DB::transaction(function () use ($user): bool {
            $pendingStudent = User::query()
                ->whereKey($user->id)
                ->where('role', 'Mahasiswa')
                ->whereNull('approved_at')
                ->lockForUpdate()
                ->first();

            if (! $pendingStudent) {
                return false;
            }

            $hasAcademicRecords = $pendingStudent->attendances()->exists()
                || $pendingStudent->submissions()->exists()
                || $pendingStudent->courseGrades()->exists();

            if ($hasAcademicRecords) {
                return false;
            }

            return (bool) $pendingStudent->delete();
        });

        if (! $deleted) {
            return back()->with('error', 'Akun tidak dapat dihapus karena sudah diverifikasi atau memiliki data akademik.');
        }

        return back()->with('success', "Akun {$user->name} berhasil dihapus.");
    }
}
