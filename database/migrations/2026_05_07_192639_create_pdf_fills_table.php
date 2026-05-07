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
        Schema::create('pdf_fills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdf_document_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default("default");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_fills');
    }
};
