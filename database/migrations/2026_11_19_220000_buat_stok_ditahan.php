<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Checkout writes this receipt in the SAME transaction as the stock decrement.
        // A release must never infer an inventory credit solely from order quantity.
        Schema::create('stok_ditahan', function (Blueprint $table) {
            $table->foreignId('item_pesanan_id')->primary()->constrained('item_pesanan')->restrictOnDelete();
            $table->foreignId('varian_produk_id')->constrained('varian_produk')->restrictOnDelete();
            $table->unsignedSmallInteger('jumlah');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_ditahan');
    }
};
