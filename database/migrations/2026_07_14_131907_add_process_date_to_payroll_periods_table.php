<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_periods', 'process_date')) {
                $table->date('process_date')->nullable()->after('status');
            }

            if (! Schema::hasColumn('payroll_periods', 'days_in_period')) {
                $table->unsignedSmallInteger('days_in_period')->nullable()->after('process_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropColumnIfExists('process_date');
            $table->dropColumnIfExists('days_in_period');
        });
    }
};
