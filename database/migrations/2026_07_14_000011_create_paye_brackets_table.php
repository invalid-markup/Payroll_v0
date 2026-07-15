<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paye_brackets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // DB spec §5.7: minimum_income, maximum_income (nullable for top bracket), base_tax_amount
            // BR §2: Formula = base_tax_amount + ((taxable_income - minimum_income) * rate_percentage)
            // BR §2: Entry condition is > minimum_income (not >=). Store exact floor, e.g. 270,000 not 270,001.
            $table->decimal('minimum_income', 15, 4)->default(0);
            $table->decimal('maximum_income', 15, 4)->nullable(); // NULL for top (uncapped) bracket
            $table->decimal('rate_percentage', 8, 4)->default(0);
            $table->decimal('base_tax_amount', 15, 4)->default(0); // Fixed tax at bottom of this bracket
            $table->date('effective_from');
            $table->timestamps();

            // DB spec §11.3: mandatory composite index for effective-dating
            $table->index(['effective_from', 'minimum_income']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paye_brackets');
    }
};
