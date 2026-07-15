<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 100);
            $table->string('name')->nullable();
            $table->decimal('rate_percentage', 8, 4)->nullable();
            $table->decimal('flat_amount', 15, 4)->nullable();
            $table->date('effective_from');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['code', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_configurations');
    }
};
