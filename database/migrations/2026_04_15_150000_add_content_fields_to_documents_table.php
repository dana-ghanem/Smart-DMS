<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->after('file_path');
            $table->longText('extracted_text')->nullable()->after('file_hash');
            $table->index(['user_id', 'file_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'file_hash']);
            $table->dropColumn(['file_hash', 'extracted_text']);
        });
    }
};
