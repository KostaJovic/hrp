<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('serial_number')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('purchase_price_cents')->nullable();
            $table->unsignedBigInteger('current_value_cents')->nullable();
            $table->date('purchased_at')->nullable();
            $table->date('warranty_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('warranty_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
