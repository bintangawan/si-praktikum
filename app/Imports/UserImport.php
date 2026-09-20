<?php

namespace App\Imports;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements SkipsEmptyRows, ToModel, WithHeadingRow
{
    // Properti untuk menampung laporan progres import
    public $successCount = 0;

    public $failMessages = [];

    public $successMessages = [];

    public function model(array $row)
    {
        $id = trim((string) ($row['id'] ?? ''));
        $email = Str::lower(trim((string) ($row['email'] ?? '')));
        $name = trim((string) ($row['name'] ?? ''));
        $role = UserRole::normalize((string) ($row['role'] ?? ''));
        $validator = Validator::make(
            compact('id', 'email', 'name', 'role'),
            [
                'id' => ['required', 'string', 'max:20'],
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'role' => ['required'],
            ]
        );

        if ($validator->fails()) {
            $label = $id !== '' ? $id : 'tanpa ID';
            $this->failMessages[] = "Baris {$label} dilewati: ".$validator->errors()->first();

            return null;
        }

        // 2. LOGIKA DUPLIKASI: Cek apakah ID atau Email sudah terdaftar di database
        $existingUser = User::where('id', $id)->orWhere('email', $email)->first();

        if ($existingUser) {
            // Jika data sudah ada, catat pesan gagal dan hentikan proses untuk baris ini
            $this->failMessages[] = "Baris ID {$id} ({$name}) gagal: ID atau Email sudah terdaftar.";

            return null;
        }

        // 4. PEMBUATAN USER: Membuat instance model User baru
        $user = new User([
            'id' => $id,
            'name' => $name,
            'email' => $email,

            /* PERUBAHAN DI SINI:
               Password tidak lagi mengambil dari $row['password'],
               tapi langsung menggunakan nilai dari variabel $id.
            */
            'password' => Hash::make($id),

            'role' => $role->value,
            'is_first_login' => true,
            'approved_at' => $role === UserRole::MAHASISWA ? null : now(),
            'approved_by' => $role === UserRole::MAHASISWA ? null : auth()->id(),
        ]);

        // Tambahkan data ke laporan sukses untuk ditampilkan di view nanti
        $this->successMessages[] = "Baris ID {$id} ({$name}) berhasil didaftarkan. Password default adalah ID user.";
        $this->successCount++;

        return $user;
    }
}
