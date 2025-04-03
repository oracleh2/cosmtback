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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('image_path')->nullable();
            $table->string('category')->nullable();
            $table->jsonb('ingredients')->nullable();
            $table->text('description')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('skin_type_target')->nullable();
            $table->jsonb('skin_concerns_target')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
