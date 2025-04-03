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
        Schema::create('skin_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained('skin_photos')->onDelete('cascade');
            $table->jsonb('skin_condition');
            $table->jsonb('skin_issues')->nullable()->after('skin_condition');
            $table->jsonb('metrics')->nullable()->after('skin_condition');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skin_analyses');
    }
};
