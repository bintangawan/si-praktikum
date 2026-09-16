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
            'limit' => ['nullable', Rule::in(['10', '25', '50', '100', 'all'])],
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
        $users = $limit === 'all' ? $query->get() : $query->paginate((int) $limit)->withQueryString();

        return view('laboran.users.index', compact('users', 'selectedRoles'));
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $user->update(['password' => Hash::make($user->id), 'is_first_login' => true]);

        return back()->with('success', "Password {$user->name} berhasil direset menggunakan ID.");
    }

    public function makeAslab(Request $request, User $user): RedirectResponse
    {
        if (! $user->hasRole(UserRole::MAHASISWA)) {
            return back()->with('error', 'Hanya mahasiswa yang dapat diangkat menjadi Aslab.');
        }

        $user->update(['role' => UserRole::ASLAB->value]);

        return back()->with('success', "{$user->name} berhasil diangkat menjadi Aslab.");
    }

    public function revokeAslab(Request $request, User $user): RedirectResponse
    {
        if (! $user->hasRole(UserRole::ASLAB)) {
            return back()->with('error', 'Pengguna tersebut bukan Aslab.');
        }

        if (Course::query()->where('aslab_id', $user->id)->exists()) {
            return back()->with('error', 'Aslab masih ditugaskan pada kelas. Ganti penugasan terlebih dahulu.');
        }

        $user->update(['role' => UserRole::MAHASISWA->value]);

        return back()->with('success', "Jabatan Aslab {$user->name} telah dicabut.");
    }
}
