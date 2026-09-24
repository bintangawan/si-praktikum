<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::enum(UserRole::class)],
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', Rule::in(['10', '25', '50', '100'])],
        ]);
        $selectedRoles = $validated['roles'] ?? [];
        $queryRoles = $selectedRoles;

        if (in_array(UserRole::MAHASISWA->value, $selectedRoles, true)) {
            $queryRoles[] = UserRole::ASLAB->value;
        }

        $query = User::query()
            ->when($queryRoles, fn ($builder) => $builder->whereIn('role', array_unique($queryRoles)))
            ->when($validated['search'] ?? null, function ($builder, $search) {
                $builder->where(fn ($nested) => $nested
                    ->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->orderBy('name');

        $limit = $validated['limit'] ?? '10';
        $users = $query->paginate((int) $limit)->withQueryString();

        return view('laboran.users.index', compact('users', 'selectedRoles'));
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $user->update(['password' => Hash::make($user->id), 'is_first_login' => true]);

        return back()->with('success', __('Password :name berhasil direset menggunakan ID.', ['name' => $user->name]));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in([UserRole::MAHASISWA->value, UserRole::ASLAB->value, UserRole::LABORAN->value])],
        ]);
        $targetRole = UserRole::from($validated['role']);

        if ($user->hasRole($targetRole)) {
            return back()->with('error', __('Role pengguna sudah menggunakan jabatan tersebut.'));
        }

        if ($user->hasRole(UserRole::MAHASISWA)) {
            if (! $user->approved_at) {
                return back()->with('error', __('Akun mahasiswa harus diverifikasi sebelum dapat diangkat.'));
            }

            if (! in_array($targetRole, [UserRole::ASLAB, UserRole::LABORAN], true)) {
                return back()->with('error', __('Mahasiswa hanya dapat diangkat menjadi Aslab atau Laboran.'));
            }

            $user->update(['role' => $targetRole->value]);

            return back()->with('success', __(':name berhasil diangkat menjadi :role.', [
                'name' => $user->name,
                'role' => __($targetRole->value),
            ]));
        }

        if ($user->hasRole(UserRole::ASLAB) && $targetRole === UserRole::MAHASISWA) {
            if (Course::query()->where('aslab_id', $user->id)->where('is_archived', false)->whereHas('semester', fn ($q) => $q->where('is_active', true))->exists()) {
                return back()->with('error', __('Aslab masih ditugaskan pada kelas aktif. Ganti penugasan terlebih dahulu.'));
            }

            $user->update(['role' => UserRole::MAHASISWA->value]);

            return back()->with('success', __('Jabatan Aslab :name telah dicabut.', ['name' => $user->name]));
        }

        return back()->with('error', __('Perubahan jabatan ini tidak diizinkan.'));
    }

    public function makeAslab(Request $request, User $user): RedirectResponse
    {
        $request->merge(['role' => UserRole::ASLAB->value]);

        return $this->updateRole($request, $user);
    }

    public function revokeAslab(Request $request, User $user): RedirectResponse
    {
        $request->merge(['role' => UserRole::MAHASISWA->value]);

        return $this->updateRole($request, $user);
    }
}
