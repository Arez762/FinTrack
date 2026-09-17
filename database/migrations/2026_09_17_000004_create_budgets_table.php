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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_limit', 15, 2);
            $table->enum('period', ['month', 'year']);
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->unique(
                ['user_id', 'category_id', 'period', 'year', 'month'],
                'budgets_user_period_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
