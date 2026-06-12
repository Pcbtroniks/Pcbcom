<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->unsignedBigInteger('producto_id')->index();
            $table->string('sku', 64)->nullable();
            $table->string('titulo');
            $table->string('modelo')->nullable();
            $table->string('marca')->nullable();
            $table->string('img_portada', 512)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('line_total', 12, 2);
            $table->json('snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
