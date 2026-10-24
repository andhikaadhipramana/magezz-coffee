<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('old_clock_in')->nullable();
            $table->timestamp('old_clock_out')->nullable();
            $table->timestamp('new_clock_in')->nullable();
            $table->timestamp('new_clock_out')->nullable();
            $table->string('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
