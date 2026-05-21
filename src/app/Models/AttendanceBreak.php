<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceBreak extends Model
{
    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_start_at',
        'break_end_at',
    ];

    // リレーション
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}