<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('flight_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['flight_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legs');
    }
};
