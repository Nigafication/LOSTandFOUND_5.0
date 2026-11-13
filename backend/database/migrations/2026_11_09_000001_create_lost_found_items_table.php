<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->enum('kind', ['found', 'lost'])->default('found');
            $table->string('category');
            $table->text('description');
            $table->string('location');
            $table->timestamp('occurred_at')->nullable();
            $table->enum('status', ['open', 'claimed', 'closed'])->default('open');
            $table->string('tone')->default('blue');
            $table->string('icon', 8)->default('◌');
            $table->timestamps();
            $table->index(['status', 'category']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_items');
    }
};
