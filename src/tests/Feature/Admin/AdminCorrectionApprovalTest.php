<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrection;
use App\Models\CorrectionBreak;

class AdminCorrectionApprovalTest extends TestCase
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

    // スタッフに紐づける元の勤怠情報
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

    // 修正申請した承認待ちのデータ
    private function createPendingCorrection(Attendance $attendance, User $staff)
    {
        return AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'requested_by' => $staff->id,
            'approved_by' => null,
            'status' => AttendanceCorrection::STATUS_PENDING,
            'requested_clock_in_at' => '2026-05-01 09:30:00',
            'requested_clock_out_at' => '2026-05-01 18:30:00',
            'requested_note' => '修正申請テスト入力',
        ]);
    }

    // 修正申請したデータに紐づく休憩
    private function createPendingCorrectionBreak(AttendanceCorrection $correction)
    {
        return CorrectionBreak::create([
            'attendance_correction_id' => $correction->id,
            'break_start_at' => '2026-05-01 12:30:00',
            'break_end_at' => '2026-05-01 13:30:00',
        ]);
    }

    // 承認済みのデータ
    private function createApprovedCorrection(Attendance $attendance, User $staff, User $admin)
    {
        return AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'requested_by' => $staff->id,
            'approved_by' => $admin->id,
            'status' => AttendanceCorrection::STATUS_APPROVED,
            'requested_clock_in_at' => '2026-05-01 10:30:00',
            'requested_clock_out_at' => '2026-05-01 19:30:00',
            'requested_note' => '承認済みテスト入力',
        ]);
    }

    //  15 : 勤怠情報修正機能（管理者）

    // Case1 勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_admin_pending_correction_requests()
    {
        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();
        $staff2 = $this->createStaff2();

        // 勤怠データ1(修正申請承認待ち)
        $attendance1 = $this->createAttendanceStaff1($staff1);
        $this->createBreakStaff1($attendance1);
        $pendingCorrection = $this->createPendingCorrection($attendance1, $staff1);
        $this->createPendingCorrectionBreak($pendingCorrection);

        // 勤怠データ2(修正申請承認済み)
        $attendance2 = $this->createAttendanceStaff2($staff2);
        $this->createBreakStaff2($attendance2);
        $this->createApprovedCorrection($attendance2, $staff2, $admin);

        // 管理者で承認待ちタブを開く
        $response = $this->actingAs($admin)->get(route('admin.stamp_correction_request.index', [
            'page' => 'pending',
        ]));

        $response->assertStatus(200);

        // 承認待ちの修正申請が表示される
        $response->assertSee('テスト　太郎');
        $response->assertSee('2026/05/01');
        $response->assertSee('修正申請テスト入力');
        $response->assertSee('詳細');

        // 承認待ちに承認済みが表示されないことも確認
        $response->assertDontSee('テスト　花子');
        $response->assertDontSee('承認済みテスト入力');
    }

    // Case2 承認済みの修正申請が全て表示されている
    public function test_admin_approved_correction_request()
    {
        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();
        $staff2 = $this->createStaff2();

        // 勤怠データ1(修正申請承認待ち)
        $attendance1 = $this->createAttendanceStaff1($staff1);
        $this->createBreakStaff1($attendance1);
        $pendingCorrection = $this->createPendingCorrection($attendance1, $staff1);
        $this->createPendingCorrectionBreak($pendingCorrection);

        // 勤怠データ2(修正申請承認済み)
        $attendance2 = $this->createAttendanceStaff2($staff2);
        $this->createBreakStaff2($attendance2);
        $this->createApprovedCorrection($attendance2, $staff2, $admin);

        // 管理者で承認済みタブを開く
        $response = $this->actingAs($admin)->get(route('admin.stamp_correction_request.index', [
            'page' => 'approved',
        ]));

        $response->assertStatus(200);

        // 承認済みの修正申請が表示される
        $response->assertSee('テスト　花子');
        $response->assertSee('2026/05/01');
        $response->assertSee('承認済みテスト入力');
        $response->assertSee('詳細');

        // 承認済みに承認待ちが表示されないことも確認
        $response->assertDontSee('テスト　太郎');
        $response->assertDontSee('修正申請テスト入力');
    }

    // Case3 修正申請の詳細内容が正しく表示されている
    public function test_admin_pending_correction_detail()
    {
        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();

        // 修正申請承認待ちのデータ
        $attendance1 = $this->createAttendanceStaff1($staff1);
        $this->createBreakStaff1($attendance1);
        $pendingCorrection = $this->createPendingCorrection($attendance1, $staff1);
        $this->createPendingCorrectionBreak($pendingCorrection);

        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', $attendance1));

        $response->assertStatus(200);

        // 詳細画面で表示される修正申請したデータ内容を確認
        $response->assertSee('テスト　太郎');
        $response->assertSee('2026年');
        $response->assertSee('5月1日');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('12:30');
        $response->assertSee('13:30');
        $response->assertSee('修正申請テスト入力');
        // 承認ボタンになっている
        $response->assertSee('承認');
    }

    // Case4 修正申請の承認処理が正しく行われる
    public function test_admin_can_approve_correction_request()
    {
        $admin = $this->createAdmin();

        $staff1 = $this->createStaff1();

        // 修正申請承認待ちのデータ
        $attendance1 = $this->createAttendanceStaff1($staff1);
        $this->createBreakStaff1($attendance1);
        $pendingCorrection = $this->createPendingCorrection($attendance1, $staff1);
        $this->createPendingCorrectionBreak($pendingCorrection);

        // 承認をする
        $response = $this->actingAs($admin)->post(route('admin.stamp_correction_request.approve', $pendingCorrection));

        $response->assertStatus(302);

        // 修正申請が承認済みになっている
        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $pendingCorrection->id,
            'attendance_id' => $attendance1->id,
            'requested_by' => $staff1->id,
            'approved_by' => $admin->id,
            'status' => AttendanceCorrection::STATUS_APPROVED,
        ]);

        // 元の勤怠情報が、修正申請の内容に更新されている
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance1->id,
            'user_id' => $staff1->id,
            'work_date' => '2026-05-01',
            'clock_in_at' => '2026-05-01 09:30:00',
            'clock_out_at' => '2026-05-01 18:30:00',
        ]);

        // 休憩時間も、修正申請の内容に更新されている
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance1->id,
            'break_start_at' => '2026-05-01 12:30:00',
            'break_end_at' => '2026-05-01 13:30:00',
        ]);
    }
}
