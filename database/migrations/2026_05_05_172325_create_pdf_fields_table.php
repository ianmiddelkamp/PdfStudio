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
        Schema::create('pdf_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdf_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('page_number');
            $table->string('field_name');
            $table->string('field_type');
            $table->float('pdf_left');
            $table->float('pdf_top');
            $table->float('pdf_width');
            $table->float('pdf_height');
            $table->float('css_left');
            $table->float('css_top');
            $table->float('css_width');
            $table->float('css_height');
            $table->string('font')->nullable();
            $table->float('font_size')->nullable();
            $table->string('text_color')->nullable();
            $table->string('background_color')->nullable();
            $table->string('border_color')->nullable();
            $table->string('border_style')->nullable();
            $table->float('border_width')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_fields');
    }
};
