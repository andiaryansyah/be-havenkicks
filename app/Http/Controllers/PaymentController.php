<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant; // FIX: tambahin ini

class PaymentController extends Controller
{
    public function callback(Request $request)
    {
        $serverKey = trim(config('midtrans.server_key'));
        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');
        $signatureKey = $request->input('signature_key');
        $transactionStatus = $request->input('transaction_status');
        $fraudStatus = $request->input('fraud_status');

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        // DEBUG - tambahin ini sebelum if
        // Log::info('DEBUG MIDTRANS', [
        //     'order_id' => $orderId,
        //     'status_code' => $statusCode,
        //     'gross_amount' => $grossAmount,
        //     'server_key_used' => $serverKey, // ini kunci, lihat ini key lama atau baru?
        //     'server_key_len' => strlen($serverKey),
        //     'expected' => $expectedSignature,
        //     'received' => $signatureKey,
        //     'match' => $expectedSignature === $signatureKey
        // ]);

        if (!hash_equals($expectedSignature, $signatureKey)) {
            Log::warning("Midtrans Invalid Signature", ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $order = Order::where('order_code', $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if (in_array($order->status, ['paid', 'canceled', 'failed'])) {
            return response()->json(['message' => 'Order already processed: '.$order->status]);
        }

        $isPaid = ($transactionStatus == 'capture' && $fraudStatus == 'accept') 
               || $transactionStatus == 'settlement';

        if ($isPaid) {
            // FIX: PAID jangan kurangi stok lagi! Stok sudah dikurangi di OrderController@checkout
            // Cukup ubah status jadi paid saja
            DB::transaction(function () use ($order) {
                $orderLocked = Order::where('id', $order->id)->lockForUpdate()->first();
                if ($orderLocked->status === 'paid') return;
                $orderLocked->update(['status' => 'paid']);
            });
            Log::info("Order {$orderId} PAID");

        } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny', 'failure'])) {
            Log::info("Order {$orderId} status {$transactionStatus} -> set to canceled");
            
            // FIX: Balikin stok HANYA jika masih pending, dan balikin VARIANT + PRODUCT
            if ($order->status == 'pending') {
                foreach ($order->items()->with('variant')->get() as $item) {
                    // balikin stok varian
                    if ($item->product_variant_id) {
                        ProductVariant::where('id', $item->product_variant_id)->increment('stock', $item->quantity);
                    }
                    // balikin stok produk induk
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }
            }
            $order->update(['status' => 'canceled']);
        }

        return response()->json(['message' => 'OK']);
    }
}