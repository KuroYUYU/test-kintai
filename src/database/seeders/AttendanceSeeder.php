<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // スタッフを3人作る
        $staff1 = User::create([
            'name' => 'スタッフ一郎',
            'email' => 'staff1@example.com',
            'password' => Hash::make('pass1111'),
            'role' => User::ROLE_STAFF,
            'email_verified_at' => now(),
        ]);

        $staff2 = User::create([
            'name' => 'スタッフ二郎',
            'email' => 'staff2@example.com',
            'password' => Hash::make('pass1111'),
            'role' => User::ROLE_STAFF,
            'email_verified_at' => now(),
        ]);

        $staff3 = User::create([
            'name' => 'スタッフ三郎',
            'email' => 'staff3@example.com',
            'password' => Hash::make('pass1111'),
            'role' => User::ROLE_STAFF,
            'email_verified_at' => now(),
        ]);

        // 今月の勤怠を作成
        $this->createAttendances($staff1, now()->startOfMonth());
        $this->createAttendances($staff2, now()->startOfMonth());
        $this->createAttendances($staff3, now()->startOfMonth());

        // 先月の勤怠を作成
        $this->createAttendances($staff1, now()->subMonth()->startOfMonth());
        $this->createAttendances($staff2, now()->subMonth()->startOfMonth());
        $this->createAttendances($staff3, now()->subMonth()->startOfMonth());
    }

    private function createAttendances(User $staff, Carbon $month)
    {
        for ($i = 1; $i <= 5; $i++) {
            $workDate = $month->copy()->addDays($i - 1);

            // 勤怠を作る
            $attendance = Attendance::create([
                'user_id' => $staff->id,
                'work_date' => $workDate->toDateString(),
                'clock_in_at' => $workDate->toDateString() . ' 09:00:00',
                'clock_out_at' => $workDate->toDateString() . ' 18:00:00',
                'status' => Attendance::STATUS_DONE,
                'note' => '',
            ]);

            // 休憩を作る
            AttendanceBreak::create([
                'attendance_id' => $attendance->id,
                'break_start_at' => $workDate->toDateString() . ' 12:00:00',
                'break_end_at' => $workDate->toDateString() . ' 13:00:00',
            ]);
        }
    }
}
