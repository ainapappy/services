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
        Schema::create('pricing_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 191)->unique();
            $table->string('name');
            $table->string('tagline');
            $table->unsignedInteger('price_monthly');
            $table->unsignedInteger('price_yearly');
            $table->string('currency', 3)->default('EUR');
            $table->json('features');
            $table->boolean('popular')->default(false);
            $table->boolean('active')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_plans');
    }
};
