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

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ["low_production","price_spike","security_change"]);
            $table->string('title');
            $table->text('message');
            $table->foreignId('district_id')->nullable()->constrained();
            $table->foreignId('commodity_id')->nullable()->constrained();
            $table->enum('severity', ["info","warning","critical"]);
            $table->boolean('is_resolved')->default(false);
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
