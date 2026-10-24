<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0)->after('notes');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal');
            $table->string('cancel_reason', 255)->nullable()->after('status');
            $table->foreignId('cancelled_by')
                ->nullable()
                ->after('cancel_reason')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });

        // Pesanan lama belum menyimpan pajak: total lama sama dengan subtotal.
        DB::table('orders')->update([
            'subtotal' => DB::raw('total'),
            'tax_amount' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancel_reason', 'cancelled_at', 'tax_amount', 'subtotal']);
        });
    }
};
