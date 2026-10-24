<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    protected $fillable = [
        'attendance_id',
        'edited_by',
        'old_clock_in',
        'old_clock_out',
        'new_clock_in',
        'new_clock_out',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'old_clock_in' => 'datetime',
            'old_clock_out' => 'datetime',
            'new_clock_in' => 'datetime',
            'new_clock_out' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
