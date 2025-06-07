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
        Schema::disableForeignKeyConstraints();

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->json('polygon_coordinates');
            $table->enum('security_level', ["low","medium","high","critical"]);
            $table->bigInteger('population');
            $table->decimal('area_hectares', 15, 2);
            $table->enum('administrative_level', ["province","regency","district","village"]);
            $table->foreignId('parent_district_id')->nullable()->constrained('districts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
