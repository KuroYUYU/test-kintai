<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrection;

class AttendanceCorrectionTest extends TestCase
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

    private function createAdmin()
    {
        return User::create([
            'name' => '管理者　太郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
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

    // 11 : 勤怠詳細情報修正機能（一般ユーザー）

    // Case1 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_clock_in_after_clock_out_error()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        // 出勤時間を退勤時間より後にする
        $response = $this->actingAs($user)->post(route('stamp_correction_request.store', $attendance), [
            'clock_in_at' => '19:00',
            'clock_out_at' => '18:00',
            'requested_note' => 'テスト入力',
        ]);

        $response->assertStatus(302);

        // ※テストケースと機能要件にて期待値の文言に差異がありました
        // 機能要件のエラーメッセージを正とし作成しています
        $response->assertSessionHasErrors([
            'clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // Case2 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_break_start_after_clock_out_error()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $this->createBreak($attendance);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        // 休憩開始時間が退勤時間より後
        $response = $this->actingAs($user)->post(route('stamp_correction_request.store', $attendance), [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                [
                    'break_start_at' => '19:00',
                    'break_end_at' => '20:00',
                ],
            ],
            'requested_note' => 'テスト入力',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.break_start_at' => '休憩時間が不適切な値です',
        ]);
    }

    // Case3 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_break_end_after_clock_out_error()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $this->createBreak($attendance);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        // 休憩終了時間が退勤時間より後
        $response = $this->actingAs($user)->post(route('stamp_correction_request.store', $attendance), [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                [
                    'break_start_at' => '17:00',
                    'break_end_at' => '18:30',
                ],
            ],
            'requested_note' => 'テスト入力',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.break_end_at' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // Case4 備考欄が未入力の場合のエラーメッセージが表示される
    public function test_requested_note_required_error()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        // 備考を未入力
        $response = $this->actingAs($user)->post(route('stamp_correction_request.store', $attendance), [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'requested_note' => '',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'requested_note' => '備考を記入してください',
        ]);
    }

    // Case5 修正申請処理が実行される
    public function test_correction_request_success()
    {
        // このテストではスタッフと管理者を使う
        $staff = $this->createStaff();
        $admin = $this->createAdmin();

        $attendance = $this->createAttendance($staff);

        $this->createBreak($attendance);

        $response = $this->actingAs($staff)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        // 正常な修正内容で修正申請
        $response = $this->actingAs($staff)->post(route('stamp_correction_request.store', $attendance), [
            'clock_in_at' => '09:30',
            'clock_out_at' => '18:30',
            'breaks' => [
                [
                    'break_start_at' => '12:30',
                    'break_end_at' => '13:30',
                ],
            ],
            'requested_note' => 'テスト入力',
        ]);

        $response->assertStatus(302);

        // データベースに反映された事を確認
        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'requested_by' => $staff->id,
            'status' => AttendanceCorrection::STATUS_PENDING,
            'requested_note' => 'テスト入力',
        ]);

        // 管理者の申請一覧画面で表示を確認
        $response = $this->actingAs($admin)->get(route('admin.stamp_correction_request.index', [
            'page' => 'pending',
        ]));

        $response->assertStatus(200);

        $response->assertSee('テスト　太郎');
        $response->assertSee('承認待ち');
        $response->assertSee('2026/05/01');
        $response->assertSee('テスト入力');
        $response->assertSee('詳細');

        // 管理者の承認画面(詳細)で表示を確認
        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', $attendance));

        $response->assertStatus(200);

        $response->assertSee('テスト　太郎');
        $response->assertSee('2026年');
        $response->assertSee('5月1日');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('12:30');
        $response->assertSee('13:30');
        $response->assertSee('テスト入力');
        $response->assertSee('承認');
    }

    // Case6 「承認待ち」にログインユーザーが行った申請が全て表示されていること
    public function test_pending_correction_request_list()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $this->createBreak($attendance);

        $response->assertStatus(200);

        // 正常な修正内容で修正申請
        $response = $this->actingAs($user)->post(route('stamp_correction_request.store', $attendance), [
            'clock_in_at' => '09:30',
            'clock_out_at' => '18:30',
            'breaks' => [
                [
                    'break_start_at' => '12:30',
                    'break_end_at' => '13:30',
                ],
            ],
            'requested_note' => 'テスト入力',
        ]);

        $response->assertStatus(302);

        // 申請一覧画面を開く
        $response = $this->actingAs($user)->get(route('stamp_correction_request.index', [
            'page' => 'pending',
        ]));

        $response->assertStatus(200);

        $response->assertSee('2026/05/01');
        $response->assertSee('承認待ち');
        $response->assertSee('テスト入力');
        $response->assertSee('詳細');
    }

    // Case7 「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_approved_correction_request_list()
    {
        $staff = $this->createStaff();

        $admin = $this->createAdmin();

        $attendance = $this->createAttendance($staff);

        $this->createBreak($attendance);

        // 承認された修正データを作成
        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'requested_by' => $staff->id,
            'approved_by' => $admin->id,
            'status' => AttendanceCorrection::STATUS_APPROVED,
            'requested_clock_in_at' => '2026-05-01 09:30:00',
            'requested_clock_out_at' => '2026-05-01 18:30:00',
            'breaks' => [
                [
                    'break_start_at' => '12:30',
                    'break_end_at' => '13:30',
                ],
            ],
            'requested_note' => 'テスト入力',
        ]);

        $response = $this->actingAs($staff)->get(route('stamp_correction_request.index', [
            'page' => 'approved',
        ]));

        $response->assertStatus(200);

        $response->assertSee('2026/05/01');
        $response->assertSee('承認済み');
        $response->assertSee('テスト入力');
        $response->assertSee('詳細');
    }

    // Case8 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
    public function test_pending_correction_detail()
    {
        $user = $this->createStaff();

        $attendance = $this->createAttendance($user);

        $this->createBreak($attendance);

        // 正常な修正内容で修正申請
        $response = $this->actingAs($user)->post(route('stamp_correction_request.store', $attendance), [
            'clock_in_at' => '09:30',
            'clock_out_at' => '18:30',
            'breaks' => [
                [
                    'break_start_at' => '12:30',
                    'break_end_at' => '13:30',
                ],
            ],
            'requested_note' => 'テスト入力',
        ]);

        $response->assertStatus(302);

        // 申請一覧画面を開く
        $response = $this->actingAs($user)->get(route('stamp_correction_request.index', [
            'page' => 'pending',
        ]));

        $response->assertStatus(200);

        // 申請一覧画面に詳細のリンクがあるかを見る
        $response->assertSee('詳細');
        $response->assertSee(route('attendance.detail', $attendance), false);

        // 詳細画面に遷移
        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        // 一応修正申請された勤怠の詳細であることも確認
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('12:30');
        $response->assertSee('13:30');
        $response->assertSee('テスト入力');
    }
}
