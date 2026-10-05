<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_rules', function (Blueprint $table) {
            $table->id();
            $table->string('keywords'); // dipisah koma
            $table->string('description');
            $table->text('response');
            $table->string('quick_button')->nullable();
            $table->boolean('forward_to_admin')->default(false);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('schedule');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('direction'); // in, out
            $table->text('body');
            $table->boolean('handled_by_bot')->default(false);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('scheduled_messages');
        Schema::dropIfExists('chatbot_rules');
    }
};
