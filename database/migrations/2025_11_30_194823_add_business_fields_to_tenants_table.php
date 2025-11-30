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
        Schema::table('tenants', function (Blueprint $table) {
            // Business Information
            $table->string('website')->nullable()->after('name');
            $table->string('phone')->nullable()->after('website');
            $table->string('email')->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
            $table->string('tax_id')->nullable()->after('address'); // ITIN/EIN

            // Primary Contact
            $table->string('contact_name')->nullable()->after('tax_id');
            $table->string('contact_title')->nullable()->after('contact_name');
            $table->string('contact_email')->nullable()->after('contact_title');
            $table->string('contact_phone')->nullable()->after('contact_email');

            // Branding
            $table->string('logo_path')->nullable()->after('contact_phone');
            $table->string('brand_color')->nullable()->after('logo_path'); // Filament Color enum name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'website',
                'phone',
                'email',
                'address',
                'tax_id',
                'contact_name',
                'contact_title',
                'contact_email',
                'contact_phone',
                'logo_path',
                'brand_color',
            ]);
        });
    }
};
