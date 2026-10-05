<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('segment'); // Rantangan, Nasi box, Kantor, Rumah sakit, Klinik, Pabrik, Sekolah, Event
            $table->string('area')->nullable();
            $table->string('address')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('preference')->nullable();
            $table->date('birthday')->nullable();
            $table->date('customer_since')->nullable();
            $table->string('value_tier')->default('Sedang'); // Tinggi, Sedang, Rendah
            $table->string('portal_token', 64)->unique()->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
