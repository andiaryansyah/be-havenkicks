<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function callback(Request $request)
    {
       $serverKey = trim(config('midtrans.server_key'));
        $hashed = hash("sha512", $request->order_id.$request->status_code.$request->gross_amount.$serverKey);

        if($hashed != $request->signature_key){
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $order = Order::where('order_code', $request->order_id)->first();
        if(!$order){
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transactionStatus = $request->transaction_status;
        $fraud = $request->fraud_status;

        if ($transactionStatus == 'capture') {
            if ($fraud == 'accept') $order->update(['status' => 'paid']);
        } else if ($transactionStatus == 'settlement') {
            $order->update(['status' => 'paid']);
        } else if ($transactionStatus == 'pending') {
            $order->update(['status' => 'pending']);
        } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $order->update(['status' => 'failed']);
        }

        Log::info("Midtrans Callback: {$order->order_code} jadi {$order->status}");
        return response()->json(['message' => 'OK']);
    }
}