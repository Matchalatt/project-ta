<?php

// Lokasi file: app/Http/Controllers/RecommendationController.php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RecommendationController extends Controller
{
    /**
     * Mendapatkan dan mengembalikan koleksi produk yang direkomendasikan.
     *
     * @param  \App\Models\Product $product Produk yang sedang dilihat.
     * @return \Illuminate\Support\Collection Koleksi produk yang direkomendasikan.
     */
    public static function getRecommendationsFor(Product $product)
    {
        // ========================================================================
        // PEMBARUAN: Path Python bersih tanpa kutip ganda tambahan.
        // PASTIKAN path ini sudah benar sesuai dengan instalasi Python di komputer Anda.
        // ========================================================================
        $pythonPath = 'C:\laragon\bin\python\Python311\python.exe';
        $scriptPath = base_path('recommend_tfidf.py');

        // ========================================================================
        // PEMBARUAN: Menggunakan fromShellCommandline untuk penanganan path yang lebih baik.
        // Ini lebih aman, terutama untuk path yang mengandung spasi.
        // ========================================================================
        $command = sprintf('"%s" "%s" %d', $pythonPath, $scriptPath, $product->id);
        $process = Process::fromShellCommandline($command);

        try {
            // Jalankan perintah
            $process->mustRun();

            // Ambil output dari script
            $output = $process->getOutput();
            $recommendedIds = json_decode($output, true);

            // Cek jika output adalah error dari script python atau JSON tidak valid
            if (json_last_error() !== JSON_ERROR_NONE || isset($recommendedIds['error'])) {
                Log::warning('Rekomendasi: Gagal decode JSON atau script mengembalikan error.', [
                    'output' => $output,
                    'product_id' => $product->id
                ]);
                return collect();
            }

            if (empty($recommendedIds) || !is_array($recommendedIds)) {
                return collect();
            }

            // ========================================================================
            // PEMBARUAN: Query yang lebih efisien untuk mengambil dan mengurutkan produk.
            // Metode FIELD() di MySQL akan mengurutkan hasil berdasarkan urutan ID
            // yang kita berikan, sehingga tidak perlu sorting lagi di sisi PHP.
            // ========================================================================
            $placeholders = implode(',', array_fill(0, count($recommendedIds), '?'));
            return Product::whereIn('id', $recommendedIds)
                          ->orderByRaw("FIELD(id, {$placeholders})", $recommendedIds)
                          ->get();

        } catch (ProcessFailedException $exception) {
            // ========================================================================
            // PEMBARUAN: Logging diaktifkan. Jika script gagal, error akan tercatat.
            // Cek file log di: storage/logs/laravel.log
            // ========================================================================
            Log::error('Rekomendasi: Proses script Python gagal.', [
                'product_id' => $product->id,
                'error' => $exception->getMessage()
            ]);
            
            return collect(); // Kembalikan collection kosong jika gagal
        }
    }
}