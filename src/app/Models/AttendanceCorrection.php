<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    protected $fillable = [
        'attendance_id',
        'requested_by',
        'approved_by',
        'status',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'requested_note',
    ];

    // statusを定数で定義
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;

    // リレーション
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // この修正申請は、誰が申請したか
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // この修正申請は、誰が承認したか
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function correctionBreaks()
    {
        return $this->hasMany(CorrectionBreak::class);
    }
}
