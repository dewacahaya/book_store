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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('isbn')->unique();
            $table->string('publisher')->nullable();
            $table->year('publication_year');
            $table->enum('availability', ['available', 'rented', 'reserved'])->default('available');
            $table->string('store_location', 100);
            $table->text('description')->nullable();
            $table->timestamps();

            // Index
            $table->index('author_id');
            $table->index('publication_year');
            $table->index('availability');
            $table->index('store_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
