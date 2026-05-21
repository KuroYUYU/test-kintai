<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'status',
        'note'
    ];

    // statusを定数で定義
    public const STATUS_OFF      = 0; // 勤務外
    public const STATUS_WORKING  = 1; // 出勤中
    public const STATUS_BREAKING = 2; // 休憩中
    public const STATUS_DONE     = 3; // 勤務終了

    // リレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class, 'attendance_id');
    }

    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
