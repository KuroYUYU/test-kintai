<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorrectionBreak extends Model
{
    protected $fillable = [
        'attendance_correction_id',
        'break_start_at',
        'break_end_at',
    ];

    // リレーション
    public function attendanceCorrection()
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }
}
