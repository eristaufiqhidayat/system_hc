<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // Rumah sakit, Kantor, Klinik, Pabrik, Sekolah
            $table->string('summary'); // 296 porsi/hari · 3 shift
            $table->unsignedInteger('daily_portions')->default(0);
            $table->string('delivery_info')->nullable();
            $table->string('pic')->nullable();
            $table->unsignedBigInteger('price_per_portion')->default(0);
            $table->unsignedSmallInteger('payment_term_days')->default(10);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->timestamps();
        });

        Schema::create('contract_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->string('note')->nullable();
        });

        Schema::create('patient_diets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('room');
            $table->string('diet_type');
            $table->string('note')->nullable();
            $table->date('valid_until')->nullable(); // null = tetap
            $table->boolean('is_new')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->date('delivered_on');
            $table->unsignedInteger('portions');
            $table->unsignedInteger('extra_portions')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_deliveries');
        Schema::dropIfExists('patient_diets');
        Schema::dropIfExists('contract_shifts');
        Schema::dropIfExists('contracts');
    }
};
