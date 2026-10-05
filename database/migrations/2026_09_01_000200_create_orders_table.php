<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // HC-2291
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('business_line'); // Rantangan, Nasi box, Kantor, Rumah sakit, Event
            $table->string('item');
            $table->unsignedInteger('portions');
            $table->date('delivery_date');
            $table->date('delivery_date_end')->nullable();
            $table->string('delivery_time')->nullable();
            $table->string('address')->nullable();
            $table->string('area')->nullable();
            $table->text('notes')->nullable();
            $table->string('channel'); // WA bot, Web, Admin, Kontrak
            $table->string('payment_method'); // QRIS, Transfer, Invoice, COD
            $table->string('payment_status'); // lunas, menunggu, dp, invoice
            $table->string('status')->default('Baru'); // Baru, Dikonfirmasi, Diproses, Dikirim, Selesai
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('orders');
    }
};
