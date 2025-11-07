<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->string('user_identifier');
            $table->tinyInteger('rating')->unsigned()->comment('1–10');
            $table->timestamps();

            // ✅ Indexes
            $table->index('book_id');
            $table->index('rating');
            $table->index(['book_id', 'created_at']);
            $table->index(['created_at', 'rating']);

            // ✅ Optional but recommended
            // $table->unique(['book_id', 'user_identifier']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
