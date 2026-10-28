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
        Schema::create('monitor_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitor_id')->constrained('monitors')->cascadeOnDelete();
            // up: healthy; degraded: healthy but slower than the
            // monitor threshold; down: no healthy answer.
            $table->enum('status', ['up', 'down', 'degraded']);
            $table->unsignedInteger('latency_ms')->nullable();
            // Safe by construction: a short wording, no payload, no
            // secret, no stack trace.
            $table->text('message')->nullable();
            // Append-only journal: no created_at/updated_at, only the
            // moment the check ran.
            $table->timestamp('checked_at');

            $table->index(['monitor_id', 'checked_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_checks');
    }
};
