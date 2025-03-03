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
        Schema::disableForeignKeyConstraints();

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('business_name');
            $table->text('address');
            $table->string('business_phone')->nullable();
            $table->string('business_email')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('website')->nullable();
            $table->decimal('default_rate', 10, 2)->default(150.00);
            $table->string('contact_name');
            $table->string('contact_title')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->boolean('send_invoices_to_contact')->default(true);
            $table->integer('payment_terms_days')->default(14);
            $table->text('invoice_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('code', 10)->unique();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
