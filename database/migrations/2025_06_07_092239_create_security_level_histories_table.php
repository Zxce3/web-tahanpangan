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

        Schema::create('security_level_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained();
            $table->enum('previous_level', ["low","medium","high","critical"]);
            $table->enum('new_level', ["low","medium","high","critical"]);
            $table->text('change_reason');
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamp('changed_at');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_level_histories');
    }
};
