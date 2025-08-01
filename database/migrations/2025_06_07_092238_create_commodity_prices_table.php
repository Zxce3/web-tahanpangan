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

        Schema::create('commodity_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained();
            $table->foreignId('commodity_id')->constrained();
            $table->decimal('price');
            $table->enum('market_type', ["producer","wholesale","retail"]);
            $table->date('recorded_date');
            $table->string('data_source');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commodity_prices');
    }
};
