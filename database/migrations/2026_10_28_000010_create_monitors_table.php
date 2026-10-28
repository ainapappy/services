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
        Schema::create('monitors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // connector: a Connector row named in "target"; gateway: the
            // internal /api/v1/health endpoint; webhook: recent failed
            // deliveries.
            $table->enum('type', ['connector', 'gateway', 'webhook']);
            // Typed by "type": {"connectorId": 3} for a connector, []
            // for the fixed platform targets. No current-state column:
            // the state is the latest monitor_check row.
            $table->json('target');
            // Above this latency an "up" answer becomes "degraded";
            // null disables the notion of degradation.
            $table->unsignedInteger('latency_threshold_ms')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
