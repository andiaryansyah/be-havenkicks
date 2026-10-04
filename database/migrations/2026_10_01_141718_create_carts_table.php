<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('carts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('cascade');
        $table->string('size')->nullable(); // simpan size untuk cepat
        $table->integer('quantity');
        $table->timestamps();

        $table->unique(['user_id', 'product_variant_id']); // 1 user tidak bisa dobel variant yang sama
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
