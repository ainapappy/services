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
        Schema::create('webhook_endpoints', function (Blueprint $table): void {
            $table->id();
            $table->string('url');
            // Encrypted at rest (model cast "encrypted"): unlike the API
            // keys, the secret must stay retrievable to sign every
            // outgoing payload. It never crosses the application in
            // plaintext twice: once at creation, once at regeneration.
            $table->text('secret');
            // Canonical event names the endpoint subscribed to.
            $table->json('events');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
