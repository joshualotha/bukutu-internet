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
            $table->string('full_name', 255)->nullable();
            $table->string('phone_number', 20)->nullable()->index();
            $table->string('email', 255)->nullable();
            $table->string('mac_address', 17)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_name', 255)->nullable();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
