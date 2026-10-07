<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return Product::with(['category','variants','images'])->latest()->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'variants' => 'nullable|array',
            'variants.*.size' => 'required|string',
            'variants.*.stock' => 'required|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'gallery' => 'nullable|array',
            'gallery.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'price' => $request->price,
            'original_price' => $request->original_price ?? $request->price,
            'discount_percent' => $request->discount_percent ?? 0,
            'stock' => $request->stock,
            'rating' => 0,
            'image' => $imagePath,
        ]);

        if($request->has('variants')){
            $variants = $request->variants;
            if(is_string($variants)) $variants = json_decode($variants, true);
            if(is_array($variants)){
                foreach($variants as $v){
                    $product->variants()->create([
                        'size' => $v['size'],
                        'stock' => $v['stock']
                    ]);
                }
            }
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $path = $file->store('products/gallery', 'public');
                $product->images()->create(['image_path' => $path]);
            }
        }

        return response()->json($product->load(['category','variants','images']), 201);
    }

    public function show(Product $product)
    {
        return $product->load(['category','variants','images']);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'sometimes|required',
            'price' => 'sometimes|numeric',
            'original_price' => 'sometimes|numeric',
            'discount_percent' => 'sometimes|integer|min:0|max:100',
            'stock' => 'sometimes|integer|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'variants' => 'sometimes|array',
            'variants.*.size' => 'required_with:variants|string',
            'variants.*.stock' => 'required_with:variants|integer|min:0',
            'image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
            'gallery' => 'sometimes|nullable|array',
            'gallery.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $product->image = $request->file('image')->store('products', 'public');
            $product->save();
        }

        $product->update($request->only([
            'category_id','name','price','original_price','discount_percent','stock','rating'
        ]));

        // FIX UTAMA: jangan delete, tapi updateOrCreate biar tidak tabrakan sama order_items
        if($request->has('variants')){
            $variants = $request->variants;
            if(is_string($variants)) $variants = json_decode($variants, true);
            if(is_array($variants)){
                foreach($variants as $v){
                    $product->variants()->updateOrCreate(
                        ['size' => $v['size']], // kunci pencariannya size
                        ['stock' => $v['stock']]
                    );
                }
                // OPTIONAL: kalau mau stok induk otomatis = total varian
                // $total = $product->variants()->sum('stock');
                // $product->update(['stock' => $total]);
            }
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $path = $file->store('products/gallery', 'public');
                $product->images()->create(['image_path' => $path]);
            }
        }

        return response()->json($product->load(['category','variants','images']));
    }

    public function destroy(Product $product)
    {
        // Cegah hapus kalau sudah ada order
        if ($product->variants()->whereHas('orderItems')->exists()) {
            return response()->json(['message' => 'Gagal hapus: produk ini sudah ada di order, tidak bisa dihapus.'], 422);
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        foreach ($product->images as $img) {
            if (Storage::disk('public')->exists($img->image_path)) {
                Storage::disk('public')->delete($img->image_path);
            }
        }
        $product->variants()->delete();
        $product->images()->delete();
        $product->delete();
        return response()->json(['message' => 'Product berhasil dihapus']);
    }
}