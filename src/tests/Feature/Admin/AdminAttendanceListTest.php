<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AdminAttendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    // 今回のテストで使う管理者と表示用のスタッフ
    private function createAdmin()
    {
        return User::create([
            'name' => '管理者　太郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function createStaff1()
    {
        return User::create([
            'name' => 'テスト　太郎',
            'email' => 'staff1@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_STAFF,
            'email_verified_at' => now(),
        ]);
    }

    private function createStaff2()
    {
        return User::create([
            'name' => 'テスト　花子',
            'email' => 'staff2@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_STAFF,
            'email_verified_at' => now(),
        ]);
    }

    // // 表示用のスタッフに紐づける勤怠情報
    private function createAttendanceStaff1(User $staff)
    {
        return Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-05-01',
            'clock_in_at' => '2026-05-01 09:00:00',
            'clock_out_at' => '2026-05-01 18:00:00',
            'status' => Attendance::STATUS_DONE,
        ]);
    }

    private function createBreakStaff1(Attendance $attendance)
    {
        return AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-05-01 12:00:00',
            'break_end_at' => '2026-05-01 13:00:00',
        ]);
    }

    private function createAttendanceStaff2(User $staff)
    {
        return Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-05-01',
            'clock_in_at' => '2026-05-01 10:00:00',
            'clock_out_at' => '2026-05-01 17:00:00',
            'status' => Attendance::STATUS_DONE,
        ]);
    }

    private function createBreakStaff2(Attendance $attendance)
    {
        return AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-05-01 12:30:00',
            'break_end_at' => '2026-05-01 13:00:00',
        ]);
    }

    //  12 : 勤怠一覧情報取得機能（管理者）

    // Case1 その日になされた全ユーザーの勤怠情報が正確に確認できる
    public function test_admin_attendance_list_all_staff_day()
    {
        // 日付を固定する
        Carbon::setTestNow('2026-05-01 20:00:00');

        $admin = $this->createAdmin();
        $staff1 = $this->createStaff1();
        $staff2 = $this->createStaff2();

        $attendance1 = $this->createAttendanceStaff1($staff1);
        $attendance2 = $this->createAttendanceStaff2($staff2);

        $this->createBreakStaff1($attendance1);
        $this->createBreakStaff2($attendance2);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertStatus(200);

        // スタッフ名が表示される
        $response->assertSee('テスト　太郎');
        $response->assertSee('テスト　花子');

        // 勤怠時間が表示される
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('17:00');

        // 休憩と実働合計が表示される
        $response->assertSee('1:00');
        $response->assertSee('0:30');
        $response->assertSee('8:00');
        $response->assertSee('6:30');
    }

    // Case2 遷移した際に現在の日付が表示される
    public function test_admin_attendance_list_current_date()
    {
        // 日付を固定する
        Carbon::setTestNow('2026-05-01 20:00:00');

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertStatus(200);

        // 日付の表示を確認
        $response->assertSee('2026年5月1日の勤怠');
        $response->assertSee('2026/05/01');
    }

    // Case3 「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_admin_attendance_list_previous_day()
    {
        // 現在日を5/2に固定し、前日5/1の勤怠を確認する
        Carbon::setTestNow('2026-05-02 20:00:00');

        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();

        $attendance1 = $this->createAttendanceStaff1($staff1);

        $this->createBreakStaff1($attendance1);

        // 前日ボタンを押した後のURLにアクセスする
        $previousDate = now()->subDay()->format('Y-m-d');

        $response = $this->actingAs($admin)->get(route('admin.attendance.index', [
            'date' => $previousDate,
        ]));

        $response->assertStatus(200);

        // 前日の日付が表示される
        $response->assertSee('2026年5月1日の勤怠');
        $response->assertSee('2026/05/01');

        // 前日の勤怠情報が表示される
        $response->assertSee('テスト　太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    // Case4 「翌日」を押下した時に次の日の勤怠情報が表示される
    public function test_admin_attendance_list_next_day()
    {
        // 現在日を4/30に固定し、翌日5/1の勤怠を確認する
        Carbon::setTestNow('2026-04-30 20:00:00');

        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();

        $attendance1 = $this->createAttendanceStaff1($staff1);

        $this->createBreakStaff1($attendance1);

        // 翌日ボタンを押した後のURLにアクセスする
        $nextDate = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($admin)->get(route('admin.attendance.index', [
            'date' => $nextDate,
        ]));

        $response->assertStatus(200);

        // 翌日の日付が表示される
        $response->assertSee('2026年5月1日の勤怠');
        $response->assertSee('2026/05/01');

        // 翌日の勤怠情報が表示される
        $response->assertSee('テスト　太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }
}
