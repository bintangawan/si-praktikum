<?php

namespace App\Http\Controllers;

use App\Imports\UserImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UserImportController extends Controller
{
    public function showImportForm()
    {
        return view('auth.import-register');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ]);

        $import = new UserImport;

        try {
            Excel::import($import, $request->file('file'));

            // Kirim semua pesan ke session
            return redirect()->back()->with([
                'import_success' => $import->successMessages,
                'import_fails' => $import->failMessages,
                'success' => "Proses selesai. {$import->successCount} data baru berhasil ditambahkan.",
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'Import gagal diproses. Periksa format dan isi file.');
        }
    }
}
