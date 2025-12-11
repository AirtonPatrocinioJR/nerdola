<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->enum('type', ['payment', 'deposit']);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2)->nullable(); // Null para valor aberto
            $table->string('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('usage_limit')->nullable()->default(1); // 1 = uso único, null = ilimitado
            $table->integer('times_used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};

