<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->decimal('basic_salary', 15, 4)->default(0);
            $table->string('currency', 10)->default('TZS');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
