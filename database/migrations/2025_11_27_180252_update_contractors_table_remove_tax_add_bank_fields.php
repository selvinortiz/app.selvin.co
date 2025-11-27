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
            // Remove tax-related fields
            $table->dropColumn(['tax_id', 'date_of_birth']);

            // Add bank information fields
            $table->string('bank_name')->nullable()->after('payment_method');
            $table->enum('account_type', ['checking', 'savings', 'other'])->nullable()->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            // Remove bank information fields
            $table->dropColumn(['bank_name', 'account_type']);

            // Restore tax-related fields
            $table->string('tax_id')->nullable()->comment('Foreign Tax ID (FTIN) or EIN/SSN')->after('country');
            $table->date('date_of_birth')->nullable()->after('tax_id');
        });
    }
};
