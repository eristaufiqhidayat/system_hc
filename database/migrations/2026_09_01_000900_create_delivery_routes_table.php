<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->date('route_date');
            $table->string('area');
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('color', 7)->default('#1B7A37');
            $table->json('map_points')->nullable();
            $table->boolean('is_late')->default(false);
            $table->string('late_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('name');
            $table->string('address');
            $table->decimal('distance_km', 6, 2)->default(0); // jarak dari dapur, dipakai untuk optimasi urutan
            $table->json('tags')->nullable();
            $table->string('note')->nullable();
            $table->string('payment_label')->default('Lunas');
            $table->boolean('collect_payment')->default(false);
            $table->string('eta')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('proof_photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_stops');
        Schema::dropIfExists('delivery_routes');
    }
};
