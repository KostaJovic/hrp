<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->unsignedInteger('recurrence_interval');
            $table->string('recurrence_unit');
            $table->date('next_due_on');
            $table->timestamps();

            $table->index('next_due_on');
        });

        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->date('performed_on');
            $table->unsignedBigInteger('cost_cents')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('performed_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('maintenance_plans');
    }
};
