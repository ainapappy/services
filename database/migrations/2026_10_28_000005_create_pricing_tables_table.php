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
        Schema::create('pricing_tables', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle');
            $table->json('faq');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pricing_table_plan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pricing_table_id')->constrained('pricing_tables')->cascadeOnDelete();
            $table->foreignId('pricing_plan_id')->constrained('pricing_plans')->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->unique(['pricing_table_id', 'pricing_plan_id']);
            $table->index('pricing_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_table_plan');
        Schema::dropIfExists('pricing_tables');
    }
};
