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
        Schema::create('pdf_fill_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdf_fill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pdf_field_id')->constrained()->cascadeOnDelete();
            $table->string('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_fill_values');
    }
};
