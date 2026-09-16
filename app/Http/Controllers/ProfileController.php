<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Course;
use App\Models\Tutorial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Ambil data yang sudah divalidasi
        $user->fill($request->safe()->except('avatar'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // LOGIKA UPLOAD AVATAR
        if ($request->hasFile('avatar')) {
            // 1. Jika user sudah punya avatar lama, hapus filenya agar storage tidak penuh
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // 2. Simpan file baru ke folder 'avatars' di dalam disk 'public'
            $path = $request->file('avatar')->store('avatars', 'public');

            // 3. Simpan path/alamat file ke kolom avatar di database
            $user->avatar = $path;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $hasAcademicRecords = $user->submissions()->exists()
            || $user->attendances()->exists()
            || Course::query()->where(fn ($query) => $query
                ->where('dosen_id', $user->id)
                ->orWhere('laboran_id', $user->id)
                ->orWhere('aslab_id', $user->id))->exists()
            || Tutorial::query()->where('created_by', $user->id)->exists();

        if ($hasAcademicRecords) {
            return back()->withErrors([
                'password' => 'Akun yang memiliki data akademik atau penugasan tidak dapat dihapus.',
            ], 'userDeletion');
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
