<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AttendanceDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    // このテストで使用するユーザーを作成しておく
    private function createStaff()
    {
        return User::create([
            'name' => 'テスト　太郎',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_STAFF,
            'email_verified_at' => now(),
        ]);
    }

    // このテストで使用する勤怠を作成しておく
    private function createAttendance(User $user)
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-01',
            'clock_in_at' => '2026-05-01 09:00:00',
            'clock_out_at' => '2026-05-01 18:00:00',
            'status' => Attendance::STATUS_DONE,
        ]);
    }

    // このテストで使用する休憩を作成しておく（休憩は必要ある部分でのみ使用）
    private function createBreak(Attendance $attendance)
    {
        return AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-05-01 12:00:00',
            'break_end_at' => '2026-05-01 13:00:00',
        ]);
    }

    // 10 : 勤怠詳細情報取得機能（一般ユーザー）

    // Case1 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    public function test_detail_name_login_user_name()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        $response->assertSee('テスト　太郎');
    }

    // Case2 勤怠詳細画面の「日付」が選択した日付になっている
    public function test_detail_day_check()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        $response->assertSee('2026年');
        $response->assertSee('5月1日');
    }

    // Case3 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function test_detail_clock_in_clock_out()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // Case4 「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_detail_break_time()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $this->createBreak($attendance);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
