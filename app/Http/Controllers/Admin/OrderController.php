<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // bisa filter ?status=paid / pending / failed
        $query = Order::with(['user:id,name,email', 'items.product'])->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    public function show($id)
    {
        $order = Order::with(['user', 'items.product'])->where('order_code', $id)->orWhere('id', $id)->firstOrFail();
        return response()->json($order);
    }
}