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
        Schema::table('pdf_fields', function (Blueprint $table) {
            $table->string('font_weight')->default('normal')->after('font');
            $table->string('text_align')->default('left')->after('font_weight');
            $table->string('data_type')->default('string')->after('text_align');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_fields', function (Blueprint $table) {
            $table->dropColumn(['font_weight', 'text_align', 'data_type']);
        });
    }
};
