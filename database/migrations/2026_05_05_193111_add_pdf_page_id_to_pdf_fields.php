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
            $table->foreignId('pdf_page_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_fields', function (Blueprint $table) {
            $table->dropForeign(['pdf_page_id']);
            $table->dropColumn('pdf_page_id');
        });
    }
};
