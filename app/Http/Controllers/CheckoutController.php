<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use Midtrans\Config;
use Midtrans\Snap;
use Exception;

class CheckoutController extends Controller
{
    /**
     * Menampilkan halaman checkout untuk pesanan baru dari keranjang.
     */
    public function index(Request $request)
    {
        $request->validate(['items' => 'required|array|min:1']);
        
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Anda harus login untuk checkout.');
        }
        
        $user = Auth::user();
        $cart = $user->cart;

        if (!$cart) {
            return redirect()->route('troli.index')->with('error', 'Keranjang tidak ditemukan.');
        }

        $selectedDbItems = CartItem::where('cart_id', $cart->id)
                                    ->whereIn('id', $request->items)
                                    ->with(['product', 'option'])
                                    ->get();
        
        $checkoutItems = [];
        foreach ($selectedDbItems as $item) {
            $checkoutItems[$item->id] = [
                "product_id"  => $item->product_id,
                "option_id"   => $item->product_option_id,
                "name"        => $item->product->name,
                "quantity"    => $item->quantity,
                "price"       => $item->option ? $item->option->price : $item->product->price,
                "image"       => $item->product->image,
                "notes"       => $item->notes,
                "option_name" => $item->option ? $item->option->name : null,
            ];
        }

        if (empty($checkoutItems)) {
            return redirect()->route('troli.index')->with('error', 'Item yang dipilih tidak valid.');
        }

        session(['checkout_items' => $checkoutItems]);
        session(['cart_item_ids_to_remove' => $request->items]);

        return view('checkout', [
            'checkoutItems' => $checkoutItems, 
            'order' => null,
            'snapToken' => null
        ]);
    }

    /**
     * Memproses permintaan checkout baru dan menghasilkan token pembayaran Midtrans.
     */
    public function process(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_whatsapp' => 'required|string|max:20',
            'delivery_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_method,delivery|nullable|string',
            'notes' => 'nullable|string',
        ]);

        $checkoutItems = session('checkout_items', []);
        if (empty($checkoutItems)) {
            return response()->json(['error' => 'Sesi checkout berakhir. Silakan ulangi dari keranjang.'], 400);
        }

        $totalPrice = array_reduce($checkoutItems, function ($carry, $item) {
            return $carry + ($item['price'] * $item['quantity']);
        }, 0);

        $order = Order::create([
            'user_id' => Auth::id(),
            'order_code' => 'SJ-' . time() . '-' . Auth::id(),
            'customer_name' => $request->customer_name,
            'customer_whatsapp' => $request->customer_whatsapp,
            'delivery_method' => $request->delivery_method,
            'delivery_address' => $request->delivery_address,
            'notes' => $request->notes,
            'total_price' => $totalPrice,
            'status' => 'unpaid',
        ]);

        $item_details_midtrans = [];
        foreach ($checkoutItems as $id => $item) {
            $order->items()->create([
                'product_id'   => $item['product_id'],
                'product_name' => $item['name'],
                'option_name'  => $item['option_name'] ?? null,
                'quantity'     => $item['quantity'],
                'price'        => $item['price'],
            ]);
            
            $item_details_midtrans[] = [
                'id' => $id, 'price' => $item['price'], 'quantity' => $item['quantity'],
                'name' => $item['name'] . ($item['option_name'] ? ' (' . $item['option_name'] . ')' : '')
            ];
        }

        if (Auth::check() && session()->has('cart_item_ids_to_remove')) {
            $cartItemIds = session('cart_item_ids_to_remove');
            CartItem::whereIn('id', $cartItemIds)->where('cart_id', Auth::user()->cart->id)->delete();
        }
        
        session()->forget(['checkout_items', 'cart_item_ids_to_remove']);
        
        try {
            $snapToken = $this->getMidtransSnapToken($order, $item_details_midtrans);
            return response()->json(['snap_token' => $snapToken, 'order_code' => $order->order_code]);
        } catch (Exception $e) {
            $order->delete();
            Log::error('Gagal membuat Snap Token untuk pesanan baru: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal membuat sesi pembayaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan halaman untuk pembayaran ulang pesanan yang sudah ada.
     */
    public function repay(Request $request, $order_code)
    {
        $order = Order::where('order_code', $order_code)
                      ->where('user_id', Auth::id())
                      ->with('items.product')
                      ->firstOrFail();

        if ($order->status !== 'unpaid') {
            return redirect()->route('order.history')->with('error', 'Pesanan ini tidak dapat dibayar ulang.');
        }

        $checkoutItems = [];
        foreach($order->items as $item) {
            $checkoutItems[] = [
                "name"        => $item->product_name, 
                "quantity"    => $item->quantity,
                "price"       => $item->price, 
                "image"       => $item->product->image ?? 'default.jpg',
                "option_name" => $item->option_name,
            ];
        }

        // =====================================================================
        // PEMBARUAN 1: Buat ID Transaksi Unik untuk Midtrans
        // =====================================================================
        $unique_transaction_id = $order->order_code . '-' . time();
        $snapToken = '';

        try {
            $items_for_midtrans = [];
            foreach($order->items as $item){
                 $items_for_midtrans[] = [
                    'id' => $item->id, 'price' => $item->price, 'quantity' => $item->quantity,
                    'name' => $item->product_name . ($item->option_name ? ' (' . $item->option_name . ')' : '')
                ];
            }
            // Kirim ID unik ke fungsi helper
            $snapToken = $this->getMidtransSnapToken($order, $items_for_midtrans, $unique_transaction_id);
        } catch (Exception $e) {
            Log::error('Gagal membuat Snap Token untuk pembayaran ulang: ' . $e->getMessage());
        }

        return view('checkout', [
            'checkoutItems' => $checkoutItems, 
            'order' => $order,
            'snapToken' => $snapToken
        ]);
    }
    
    /**
     * Fungsi helper PRIVATE untuk menghasilkan Snap Token.
     */
    private function getMidtransSnapToken($order, $item_details, $transaction_id = null)
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $params = [
            'transaction_details' => [
                // =====================================================================
                // PEMBARUAN 2: Gunakan ID unik jika ada, jika tidak, gunakan kode order asli
                // =====================================================================
                'order_id' => $transaction_id ?: $order->order_code,
                'gross_amount' => $order->total_price,
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'phone' => $order->customer_whatsapp,
            ],
            'item_details' => $item_details,
        ];
        
        return Snap::getSnapToken($params);
    }
}