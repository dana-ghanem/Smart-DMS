<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who uploaded
            $table->string('title');
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path'); // store the path to the uploaded file
            $table->string('category')->nullable(); // or use category_id if you have a categories table
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};