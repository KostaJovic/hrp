<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->unsignedBigInteger('amount_cents');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->date('spent_on');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('recurrence_interval')->nullable();
            $table->string('recurrence_unit')->nullable();
            $table->date('next_due_on')->nullable();
            $table->timestamps();

            $table->index('spent_on');
            $table->index('next_due_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
