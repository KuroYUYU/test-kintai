<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // スタッフと管理者を判定するための定数
    public const ROLE_STAFF = 0;
    public const ROLE_ADMIN = 1;

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    // リレーション
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // ユーザーが申請した勤怠修正申請
    public function requestedCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class, 'requested_by');
    }

    // 管理者が承認した勤怠修正申請
    public function approvedCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class, 'approved_by');
    }
}
