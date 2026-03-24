<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
   
{
    Schema::table('documents', function (Blueprint $table) {
        $table->string('author')->nullable()->after('title');
    });

       Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('author'); // make sure this column exists
    $table->text('description')->nullable();
    $table->string('file_path');
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};