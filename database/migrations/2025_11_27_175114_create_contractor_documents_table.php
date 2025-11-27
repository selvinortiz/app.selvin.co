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

        Schema::create('contractor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', [
                'contractor_agreement',
                'w8ben',
                'w9',
                '1099',
                'other'
            ])->default('other');
            $table->string('name')->nullable()->comment('Optional document name/description');
            $table->string('file_path');
            $table->string('file_name')->nullable()->comment('Original filename');
            $table->integer('file_size')->nullable()->comment('File size in bytes');
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->index(['contractor_id', 'document_type']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contractor_documents');
    }
};
