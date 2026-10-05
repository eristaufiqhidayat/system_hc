<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('selling_price')->default(0);
            $table->boolean('recipe_locked')->default(true);
            $table->decimal('rating', 2, 1)->nullable();
            $table->timestamps();
        });

        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('component');
            $table->string('amount');
            $table->unsignedBigInteger('cost')->default(0);
        });

        Schema::create('menu_rotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('week'); // 1..4
            $table->unsignedTinyInteger('weekday'); // 1 Senin .. 5 Jumat
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->unique(['week', 'weekday']);
        });

        Schema::create('diet_variants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rule');
            $table->boolean('active')->default(true);
        });

        Schema::create('production_items', function (Blueprint $table) {
            $table->id();
            $table->date('production_date');
            $table->string('menu_name');
            $table->unsignedInteger('office_portions')->default(0);
            $table->unsignedInteger('rantang_portions')->default(0);
            $table->unsignedInteger('hospital_portions')->default(0);
            $table->unsignedInteger('event_portions')->default(0);
            $table->string('diet_notes')->nullable();
            $table->string('cook')->nullable();
            $table->string('status')->default('Belum'); // Belum, Persiapan, Dimasak, Selesai
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_items');
        Schema::dropIfExists('diet_variants');
        Schema::dropIfExists('menu_rotations');
        Schema::dropIfExists('recipe_items');
        Schema::dropIfExists('menus');
    }
};
