<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catering_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('venue_area')->nullable();
            $table->unsignedInteger('pax');
            $table->date('event_date');
            $table->string('event_time')->nullable();
            $table->unsignedTinyInteger('stage')->default(0); // 0 Permintaan .. 4 Selesai
            $table->unsignedBigInteger('price_per_pax')->default(0);
            $table->unsignedBigInteger('equipment_cost')->default(0);
            $table->boolean('dp_received')->default(false);
            $table->json('menu')->nullable();
            $table->timestamps();
        });

        Schema::create('event_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catering_event_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('done')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_checklist_items');
        Schema::dropIfExists('catering_events');
    }
};
