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
        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            // The exact envelope sent on the wire: event, occurred_at, data.
            $table->json('payload');
            // pending: waiting or mid-campaign; success: 2xx answer;
            // failed: retry campaign exhausted.
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            // Real attempts, persisted before each try.
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('response_code')->nullable();
            // Safe by construction: a short wording, no body, no secret,
            // no stack trace.
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_endpoint_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
