<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use Midtrans\Config;
use Midtrans\Notification;
use Exception;

class MidtransController extends Controller
{
    /**
     * Menangani notifikasi pembayaran dari Midtrans (webhook).
     */
    public function notificationHandler(Request $request)
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');

        Log::info('Webhook dari Midtrans diterima:', $request->all());

        try {
            $notification = new Notification();

            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status;
            $orderCodeFromMidtrans = $notification->order_id;

            // =================================================================
            // PEMBARUAN UTAMA DI SINI
            // =================================================================
            // Format kode order asli: SJ-timestamp-userid
            // Format kode pembayaran ulang: SJ-timestamp-userid-timestamp_repay
            // Kita perlu mengambil kode order asli sebelum mencari di database.

            $parts = explode('-', $orderCodeFromMidtrans);
            
            // Jika ada lebih dari 3 bagian (artinya ini pembayaran ulang), potong menjadi 3 bagian pertama
            if (count($parts) > 3) {
                $originalOrderCode = $parts[0] . '-' . $parts[1] . '-' . $parts[2];
            } else {
                $originalOrderCode = $orderCodeFromMidtrans;
            }
            
            // Cari pesanan menggunakan kode asli
            $order = Order::where('order_code', $originalOrderCode)->first();
            // =================================================================
            // AKHIR PEMBARUAN
            // =================================================================


            if (!$order) {
                Log::warning("Webhook diabaikan: Order dengan code asli {$originalOrderCode} tidak ditemukan.");
                return response()->json(['message' => 'Order not found.'], 404);
            }

            if ($order->status == 'paid') {
                 Log::info("Webhook diabaikan: Order {$originalOrderCode} sudah lunas.");
                 return response()->json(['message' => 'Order already paid.'], 200);
            }

            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $order->update(['status' => 'paid']);
                    Log::info("Status order {$originalOrderCode} diupdate menjadi PAID (via capture).");
                }
            } else if ($transactionStatus == 'settlement') {
                $order->update(['status' => 'paid']);
                Log::info("Status order {$originalOrderCode} diupdate menjadi PAID (via settlement).");
            } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
                $order->update(['status' => 'cancelled']);
                Log::info("Status order {$originalOrderCode} diupdate menjadi CANCELLED.");
            } else if ($transactionStatus == 'pending') {
                Log::info("Status order {$originalOrderCode} masih PENDING, tidak ada update.");
            }

            return response()->json(['message' => 'Notification processed successfully.'], 200);

        } catch (Exception $e) {
            Log::error("Midtrans Webhook Error: " . $e->getMessage());
            return response()->json(['message' => 'Error processing notification.'], 500);
        }
    }
}