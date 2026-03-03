<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotent_requests', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key');
            $table->string('route');
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body')->nullable();
            $table->timestamps();

            $table->unique(['idempotency_key', 'route']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotent_requests');
    }
};
