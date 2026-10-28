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
        Schema::create('monitor_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitor_id')->constrained('monitors')->cascadeOnDelete();
            // Safe by construction: a short wording, no payload, no
            // secret, no stack trace.
            $table->text('message');
            // warning: degraded; critical: down.
            $table->enum('level', ['warning', 'critical']);
            // Open while null: the return to a healthy state (or a
            // manual resolution) stamps this column.
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_alerts');
    }
};
