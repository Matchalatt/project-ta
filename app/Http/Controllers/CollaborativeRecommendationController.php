<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Http\Request;

class CollaborativeRecommendationController extends Controller
{
    /**
     * Menjalankan script Python untuk mendapatkan rekomendasi.
     *
     * @param string $mode 'item' atau 'user'
     * @param string $data ID produk (dipisahkan koma) atau ID user
     * @return \Illuminate\Support\Collection
     */
    private static function runPythonScript(string $mode, string $data)
    {
        // Pastikan path Python dan script sudah benar
        $pythonPath = 'C:\laragon\bin\python\Python311\python.exe'; 
        $scriptPath = base_path('recommend_knn.py');

        $command = sprintf('"%s" "%s" %s %s', $pythonPath, $scriptPath, $mode, $data);
        $process = Process::fromShellCommandline($command);

        try {
            $process->mustRun();

            $output = $process->getOutput();
            $recommendedIds = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE || isset($recommendedIds['error']) || !is_array($recommendedIds)) {
                Log::warning('Rekomendasi Kolaboratif: Gagal decode JSON atau script mengembalikan error.', [
                    'mode' => $mode,
                    'input_data' => $data,
                    'output' => $output
                ]);
                return collect();
            }

            if (empty($recommendedIds)) {
                return collect();
            }
            
            // Mengambil data produk berdasarkan ID dan mengurutkannya sesuai hasil dari Python
            $placeholders = implode(',', array_fill(0, count($recommendedIds), '?'));
            return Product::whereIn('id', $recommendedIds)
                          ->orderByRaw("FIELD(id, {$placeholders})", $recommendedIds)
                          ->get();

        } catch (ProcessFailedException $exception) {
            Log::error('Rekomendasi Kolaboratif: Proses script Python gagal.', [
                'mode' => $mode,
                'input_data' => $data,
                'error' => $exception->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * [MODE ITEM] - Mendapatkan rekomendasi berdasarkan item yang ada di keranjang.
     *
     * @param  array $productIds Array berisi ID produk di keranjang.
     * @return \Illuminate\Support\Collection
     */
    public static function getRecommendationsForCart(array $productIds)
    {
        if (empty($productIds)) {
            return collect();
        }
        $productIdsString = implode(',', $productIds);
        return self::runPythonScript('item', $productIdsString);
    }

    /**
     * [BARU - MODE USER] - Mendapatkan rekomendasi personal untuk dashboard.
     *
     * @param  int $userId ID pengguna yang sedang login.
     * @return \Illuminate\Support\Collection
     */
    public static function getRecommendationsForUser(int $userId)
    {
        return self::runPythonScript('user', (string)$userId);
    }
}