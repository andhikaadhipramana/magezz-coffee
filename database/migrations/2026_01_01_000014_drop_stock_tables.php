<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur stok (bahan baku, resep/BOM, pergerakan stok) dihapus total.
     * Menu kini hanya ditentukan aktif/nonaktif tanpa perhitungan stok.
     */
    public function up(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredients');
    }

    public function down(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('unit', 20);
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->timestamps();
            $table->unique(['menu_id', 'ingredient_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('quantity', 12, 2);
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};
