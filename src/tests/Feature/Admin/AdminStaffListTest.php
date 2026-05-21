<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AdminStaffListTest extends TestCase
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

    private function createStaff3()
    {
        return User::create([
            'name' => 'テスト　二郎',
            'email' => 'staff3@example.com',
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

    //  14 : 勤怠一覧情報取得機能（管理者）

    // Case1 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_all_staff_list()
    {
        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();
        $staff2 = $this->createStaff2();
        $staff3 = $this->createStaff3();

        $response = $this->actingAs($admin)->get(route('admin.staff.index'));

        $response->assertStatus(200);

        // スタッフ名が表示される
        $response->assertSee('テスト　太郎');
        $response->assertSee('テスト　花子');
        $response->assertSee('テスト　二郎');

        // メールアドレスが表示される
        $response->assertSee('staff1@example.com');
        $response->assertSee('staff2@example.com');
        $response->assertSee('staff3@example.com');
    }

    // Case2 ユーザーの勤怠情報が正しく表示される
    public function test_admin_staff_attendance_list()
    {
        // 日付は固定しておく
        Carbon::setTestNow('2026-05-01 20:00:00');

        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();

        $attendance1 = $this->createAttendanceStaff1($staff1);

        $this->createBreakStaff1($attendance1);

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', $staff1));

        $response->assertStatus(200);

        // スタッフ名の月次勤怠が表示される
        $response->assertSee('テスト　太郎さんの勤怠');
        //日付、出勤、退勤、休憩合計、実働、詳細
        $response->assertSee('05/01');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
        $response->assertSee('詳細');
    }

    // Case3 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_admin_staff_attendance_list_previous_month()
    {
        // 日付は固定しておく
        Carbon::setTestNow('2026-05-01 20:00:00');

        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();

        $previousMonth = now()->subMonth()->format('Y-m');

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', [
            'user' => $staff1->id,
            'month' => $previousMonth,
        ]));

        $response->assertStatus(200);

        $response->assertSee('テスト　太郎さんの勤怠');
        $response->assertSee('2026/04');
    }

    // Case4 「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_admin_staff_attendance_list_next_month()
    {
        // 日付は固定しておく
        Carbon::setTestNow('2026-05-01 20:00:00');

        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();

        $nextMonth = now()->addMonth()->format('Y-m');

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', [
            'user' => $staff1->id,
            'month' => $nextMonth,
        ]));

        $response->assertStatus(200);

        $response->assertSee('テスト　太郎さんの勤怠');
        $response->assertSee('2026/06');
    }

    // Case5 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_admin_staff_attendance_list_show_detail()
    {
        // 日付は固定しておく
        Carbon::setTestNow('2026-05-01 20:00:00');

        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();

        $attendance1 = $this->createAttendanceStaff1($staff1);

        $this->createBreakStaff1($attendance1);

        $response = $this->actingAs($admin)->get(route('admin.attendance.staff', $staff1));

        $response->assertStatus(200);

        // 「詳細」リンクの遷移先が、その勤怠の詳細ページになっている
        $response->assertSee(route('admin.attendance.detail', $attendance1), false);

        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', $attendance1));

        $response->assertStatus(200);
    }
}
