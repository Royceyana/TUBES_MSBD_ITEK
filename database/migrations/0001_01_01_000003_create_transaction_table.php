<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Semua nilai uang disimpan sebagai bilangan bulat rupiah (tanpa desimal),
     * sama seperti yang dipakai UI kasir.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            // Kasir yang membuat transaksi
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // pending = pesanan tersimpan (stok sudah dipotong), belum dibayar
            // paid    = lunas, terkunci
            // void    = dibatalkan sebelum dibayar (stok dikembalikan)
            $table->string('status', 10)->default('pending')->index();

            $table->unsignedBigInteger('subtotal');
            $table->string('discount_type', 10)->default('none'); // none | percent | amount
            $table->unsignedInteger('discount_value')->default(0);  // angka yang diinput kasir
            $table->unsignedBigInteger('discount_amount')->default(0); // nilai diskon dalam rupiah
            $table->unsignedBigInteger('total');

            // Diisi saat pembayaran
            $table->string('payment_method', 10)->nullable(); // cash | qris | debit
            $table->unsignedBigInteger('paid_amount')->nullable();
            $table->unsignedBigInteger('change_amount')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            $table->timestamps();
        });

        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            // Belum ada foreign key ke tabel menus karena tabelnya belum dibuat
            // (dikerjakan anggota lain). FK ditambahkan lewat migration terpisah.
            $table->unsignedBigInteger('menu_id')->index();
            // Salinan nama & harga saat transaksi, supaya struk lama tidak berubah
            // walaupun menu/harga diedit di kemudian hari.
            $table->string('menu_name');
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
        Schema::dropIfExists('transactions');
    }
};
