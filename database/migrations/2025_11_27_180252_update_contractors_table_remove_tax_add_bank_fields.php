<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            if (Schema::hasColumn('contractors', 'tax_id')) {
                $table->dropColumn('tax_id');
            }

            if (Schema::hasColumn('contractors', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }

            if (! Schema::hasColumn('contractors', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('payment_method');
            }

            if (! Schema::hasColumn('contractors', 'account_type')) {
                $table->enum('account_type', ['checking', 'savings', 'other'])->nullable()->after('bank_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            if (Schema::hasColumn('contractors', 'bank_name')) {
                $table->dropColumn('bank_name');
            }

            if (Schema::hasColumn('contractors', 'account_type')) {
                $table->dropColumn('account_type');
            }

            if (! Schema::hasColumn('contractors', 'tax_id')) {
                $table->string('tax_id')->nullable()->comment('Foreign Tax ID (FTIN) or EIN/SSN')->after('country');
            }

            if (! Schema::hasColumn('contractors', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('tax_id');
            }
        });
    }
};
