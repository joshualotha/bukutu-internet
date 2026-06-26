<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesapal_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->json('payload');
            $table->string('ipn_type', 50)->nullable();
            $table->boolean('processed')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesapal_webhook_logs');
    }
};
