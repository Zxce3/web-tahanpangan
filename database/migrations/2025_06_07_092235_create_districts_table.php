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
            $table->foreignId('regency_id')->nullable()->constrained('districts');
            $table->string('province')->nullable();
            $table->string('geojson_file_path')->nullable(); // Store file path instead of coordinates
            $table->enum('security_level', ["low","medium","high","critical"])->default('medium');
            $table->bigInteger('population')->nullable();
            $table->decimal('area_hectares', 15, 2)->nullable();
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
