<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('package'); // Harian, Mingguan, Bulanan
            $table->unsignedSmallInteger('total_days');
            $table->unsignedSmallInteger('remaining_days');
            $table->unsignedSmallInteger('portions')->default(1);
            $table->string('preference')->nullable();
            $table->string('delivery_window')->default('11.00–12.00');
            $table->date('starts_at');
            $table->date('paused_until')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_skips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->date('skip_date');
            $table->unique(['subscription_id', 'skip_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_skips');
        Schema::dropIfExists('subscriptions');
    }
};
