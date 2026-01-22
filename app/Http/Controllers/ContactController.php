<?php

// Mendefinisikan namespace untuk controller, sesuai dengan lokasinya di dalam folder app/Http/Controllers
namespace App\Http\Controllers;

// Mengimpor class-class yang akan digunakan
use App\Models\Complaint;   // Model untuk berinteraksi dengan tabel 'complaints'
use Illuminate\Http\Request; // Class untuk mengelola data dari request HTTP

class ContactController extends Controller
{
    /**
     * Menyimpan data baru dari form kontak (keluhan) ke dalam database.
     * Method ini akan dipanggil ketika user menekan tombol submit pada halaman kontak.
     *
     * @param  \Illuminate\Http\Request  $request Data yang dikirim dari form
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Langkah 1: Validasi input dari form
        // Memastikan semua field diisi dengan benar sebelum diproses lebih lanjut.
        // - name: wajib diisi, berupa teks, maksimal 255 karakter.
        // - email: wajib diisi, harus berformat email yang valid, maksimal 255 karakter.
        // - message: wajib diisi, berupa teks.
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        // Langkah 2: Simpan data yang sudah tervalidasi ke dalam database.
        // Menggunakan metode 'create' pada model Complaint untuk membuat record baru.
        Complaint::create($validatedData);

        // Langkah 3: Arahkan kembali pengguna ke halaman sebelumnya.
        // Sertakan juga 'flash message' dengan kunci 'success' untuk memberikan notifikasi.
        return redirect()->back()->with('success', 'Pesan Anda telah berhasil dikirim! Terima kasih.');
    }
}