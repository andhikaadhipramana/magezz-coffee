<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->after('paid_by')->constrained('transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaction_id');
            $table->dropConstrainedForeignId('paid_by');
            $table->dropColumn('paid_at');
        });
    }
};
