<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('UGX');
            $table->string('provider', 50)->default('pesapal');
            $table->string('provider_reference', 255)->nullable();
            $table->string('provider_tracking_id', 255)->nullable()->index();
            $table->string('payment_method', 50)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->json('response_payload')->nullable();
            $table->string('confirmation_code', 255)->nullable();
            $table->timestamp('payment_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
