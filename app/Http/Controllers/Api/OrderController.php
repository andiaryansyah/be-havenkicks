<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
         $request->validate(['shipping_address' => 'required|string']);
    $carts = Cart::with('product','variant')->where('user_id', $request->user()->id)->get();
    if($carts->isEmpty()) return response()->json(['message' => 'Cart kosong'], 400);

    return \Illuminate\Support\Facades\DB::transaction(function() use ($request, $carts) {
        $total = 0;
        foreach($carts as $cart){
            if($cart->variant->stock < $cart->quantity){
                throw new \Exception("Stok size {$cart->size} hanya sisa {$cart->variant->stock}");
            }
            $total += (int) $cart->product->price * $cart->quantity;
        }

        $order = \App\Models\Order::create([
            'user_id' => $request->user()->id,
            'order_code' => 'HVK-'.date('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(4)),
            'total_price' => (int) $total,
            'status' => 'pending',
            'shipping_address' => $request->shipping_address
        ]);

        $item_details = [];
        foreach($carts as $cart){
            $order->items()->create([
                'product_id' => $cart->product_id,
                'product_variant_id' => $cart->product_variant_id,
                'size' => $cart->size,
                'quantity' => $cart->quantity,
                'price' => (int) $cart->product->price,
            ]);
            $cart->variant->decrement('stock', $cart->quantity);
            $item_details[] = [
                'id' => $cart->product_id,
                'price' => (int) $cart->product->price,
                'quantity' => $cart->quantity,
                'name' => $cart->product->name.' - Size '.$cart->size,
            ];
        }
        \App\Models\Cart::where('user_id', $request->user()->id)->delete();

        // === INTEGRASI MIDTRANS DIMULAI DISINI ===
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_code,
                'gross_amount' => (int) $order->total_price,
            ],
            'customer_details' => [
                'first_name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
            'item_details' => $item_details
        ];

        $snapToken = Snap::getSnapToken($params);
        $order->update([
            'snap_token' => $snapToken,
            'payment_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/'.$snapToken
        ]);

        return response()->json([
            'message' => 'Checkout berhasil, silahkan bayar',
            'order' => $order->load('items.product'),
            'snap_token' => $snapToken,
            'payment_url' => $order->payment_url
        ], 201);
    });
    }

    public function index(Request $request){
        return Order::with('items.product')->where('user_id', $request->user()->id)->latest()->get();
    }
}