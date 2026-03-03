<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leg_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('origin', 10);
            $table->string('destination', 10);
            $table->dateTime('departure');
            $table->dateTime('arrival');
            $table->string('cabin_class', 5);
            $table->string('airline', 10);
            $table->string('flight_number', 20);
            $table->timestamps();

            $table->index(['leg_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segments');
    }
};
