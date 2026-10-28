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
        Schema::create('connectors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('base_url');
            // Encrypted JSON payload (model cast "encrypted:array"): the
            // plaintext credentials never touch the database.
            $table->text('credentials');
            $table->string('status')->default('unknown');
            $table->timestamp('last_tested_at')->nullable();
            // Safe by design: no secret, no payload, no stack trace.
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connectors');
    }
};
