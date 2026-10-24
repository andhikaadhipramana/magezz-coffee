<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('attendances')
            ->select('user_id', 'date')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('user_id', 'date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $records = DB::table('attendances')
                ->where('user_id', $duplicate->user_id)
                ->whereDate('date', $duplicate->date)
                ->orderBy('id')
                ->get();

            $keeper = $records->first();
            $clockIn = $records->pluck('clock_in')->filter()->sort()->first();
            $clockOut = $records->pluck('clock_out')->filter()->sort()->last();
            $notes = $records->pluck('notes')->filter()->unique()->implode(' | ');

            DB::table('attendances')
                ->where('id', $keeper->id)
                ->update([
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'notes' => $notes !== '' ? $notes : null,
                    'updated_at' => now(),
                ]);

            DB::table('attendances')
                ->whereIn('id', $records->pluck('id')->skip(1)->all())
                ->delete();
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date']);
        });
    }
};
