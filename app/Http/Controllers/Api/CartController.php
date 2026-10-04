<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // LIHAT CART USER YANG LOGIN
    public function index(Request $request)
    {
        $carts = Cart::with(['product.images', 'variant'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $total = $carts->sum(function($cart){
            return $cart->product->price * $cart->quantity;
        });

        return response()->json([
            'carts' => $carts,
            'total_price' => $total,
            'count' => $carts->count()
        ]);
    }

    // ADD TO CART
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'size' => 'required|string',
            'quantity' => 'required|integer|min:1'
        ]);

        // Cari variant berdasarkan product_id + size
        $variant = ProductVariant::where('product_id', $request->product_id)
                    ->where('size', $request->size)
                    ->first();

        if(!$variant){
            return response()->json(['message' => 'Size '.$request->size.' tidak tersedia untuk product ini'], 404);
        }

        if($variant->stock < $request->quantity){
            return response()->json(['message' => 'Stok size '.$request->size.' hanya sisa '.$variant->stock], 400);
        }

        // Cek apakah user sudah punya variant ini di cart?
        $cart = Cart::where('user_id', $request->user()->id)
                ->where('product_variant_id', $variant->id)
                ->first();

        if($cart){
            // Jika sudah ada, tambah quantity nya
            $newQty = $cart->quantity + $request->quantity;
            if($variant->stock < $newQty){
                return response()->json(['message' => 'Total quantity melebihi stok. Sisa stok: '.$variant->stock], 400);
            }
            $cart->update(['quantity' => $newQty]);
        } else {
            // Jika belum ada, buat baru
            $cart = Cart::create([
                'user_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'product_variant_id' => $variant->id,
                'size' => $request->size,
                'quantity' => $request->quantity
            ]);
        }

        return response()->json($cart->load(['product','variant']), 201);
    }

    // UPDATE QUANTITY
    public function update(Request $request, Cart $cart)
    {
        // Pastikan cart ini milik user yang login
        if($cart->user_id !== $request->user()->id){
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['quantity' => 'required|integer|min:1']);

        if($cart->variant->stock < $request->quantity){
            return response()->json(['message' => 'Stok tidak cukup, sisa '.$cart->variant->stock], 400);
        }

        $cart->update(['quantity' => $request->quantity]);
        return response()->json($cart->load(['product','variant']));
    }

    // HAPUS 1 ITEM CART
    public function destroy(Request $request, Cart $cart)
    {
        if($cart->user_id !== $request->user()->id){
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $cart->delete();
        return response()->json(['message' => 'Item dihapus dari cart']);
    }

    // KOSONGKAN CART
    public function clear(Request $request)
    {
        Cart::where('user_id', $request->user()->id)->delete();
        return response()->json(['message' => 'Cart dikosongkan']);
    }
}