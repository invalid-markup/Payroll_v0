<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('audit_event_type');
            $table->string('model')->nullable();
            $table->uuid('model_id')->nullable();
            $table->string('ip_address', 45)->nullable(); // VARCHAR(45) supports both IPv4 and IPv6 (DB spec §4.1)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['model', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
