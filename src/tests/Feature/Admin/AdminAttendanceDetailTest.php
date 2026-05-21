<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AdminAttendanceDetailTest extends TestCase
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

    private function createStaff()
    {
        return User::create([
            'name' => 'テスト　太郎',
            'email' => 'staff1@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_STAFF,
            'email_verified_at' => now(),
        ]);
    }

    // 表示用のスタッフに紐づける勤怠情報
    private function createAttendanceStaff(User $staff)
    {
        return Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-05-01',
            'clock_in_at' => '2026-05-01 09:00:00',
            'clock_out_at' => '2026-05-01 18:00:00',
            'status' => Attendance::STATUS_DONE,
        ]);
    }

    private function createBreakStaff(Attendance $attendance)
    {
        return AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-05-01 12:00:00',
            'break_end_at' => '2026-05-01 13:00:00',
        ]);
    }

    //  13 : 勤怠詳細情報取得・修正機能（管理者）

    // Case1 勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_admin_attendance_detail_show()
    {
        $admin = $this->createAdmin();

        $staff = $this->createStaff();

        $attendance = $this->createAttendanceStaff($staff);

        $this->createBreakStaff($attendance);

        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', $attendance));

        $response->assertStatus(200);

        // 詳細画面での表示を確認
        $response->assertSee('テスト　太郎');
        $response->assertSee('2026年');
        $response->assertSee('5月1日');

        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    // Case2 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_admin_clock_in_after_clock_out_error()
    {
        $admin = $this->createAdmin();

        $staff = $this->createStaff();

        $attendance = $this->createAttendanceStaff($staff);

        $this->createBreakStaff($attendance);

        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', $attendance));

        $response->assertStatus(200);

        // 出勤開始時間が退勤時間より後
        $response = $this->actingAs($admin)->post(route('admin.attendance.update', $attendance), [
            'clock_in_at' => '20:00',
            'clock_out_at' => '19:00',
            // 休憩は一応空欄にする
            'breaks' => [
                [
                    'break_start_at' => '',
                    'break_end_at' => '',
                ],
            ],
            'note' => 'テスト入力',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // Case3 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_admin_break_start_after_clock_out_error()
    {
        $admin = $this->createAdmin();

        $staff = $this->createStaff();

        $attendance = $this->createAttendanceStaff($staff);

        $this->createBreakStaff($attendance);

        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', $attendance));

        $response->assertStatus(200);

        // 休憩開始時間が退勤時間より後
        $response = $this->actingAs($admin)->post(route('admin.attendance.update', $attendance), [
            'clock_in_at' => '10:00',
            'clock_out_at' => '19:00',
            'breaks' => [
                [
                    'break_start_at' => '20:00',
                    'break_end_at' => '21:00',
                ],
            ],
            'note' => 'テスト入力',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.break_start_at' => '休憩時間が不適切な値です',
        ]);
    }

    // Case4 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_admin_break_end_after_clock_out_error()
    {
        $admin = $this->createAdmin();

        $staff = $this->createStaff();

        $attendance = $this->createAttendanceStaff($staff);

        $this->createBreakStaff($attendance);

        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', $attendance));

        $response->assertStatus(200);

        // 休憩終了時間が退勤時間より後
        $response = $this->actingAs($admin)->post(route('admin.attendance.update', $attendance), [
            'clock_in_at' => '10:00',
            'clock_out_at' => '19:00',
            'breaks' => [
                [
                    'break_start_at' => '18:30',
                    'break_end_at' => '19:30',
                ],
            ],
            'note' => 'テスト入力',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.break_end_at' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // Case5 備考欄が未入力の場合のエラーメッセージが表示される
    public function test_admin_note_required_error()
    {
        $admin = $this->createAdmin();

        $staff = $this->createStaff();

        $attendance = $this->createAttendanceStaff($staff);

        $this->createBreakStaff($attendance);

        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', $attendance));

        $response->assertStatus(200);

        // 備考が未入力
        $response = $this->actingAs($admin)->post(route('admin.attendance.update', $attendance), [
            'clock_in_at' => '10:00',
            'clock_out_at' => '19:00',
            'breaks' => [
                [
                    'break_start_at' => '13:00',
                    'break_end_at' => '14:00',
                ],
            ],
            'note' => '',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }

    // ※テストケースにはありませんが、１つ追加でテストを作成しました
    // 正常系の確認がなかったのが気になり作成に至りました
    // Case6 管理者はスタッフの勤怠を修正し更新することができ、更新内容がDBに保存される
    public function test_admin_staff_attendance_update()
    {
        $admin = $this->createAdmin();

        $staff = $this->createStaff();

        $attendance = $this->createAttendanceStaff($staff);

        $this->createBreakStaff($attendance);

        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', $attendance));

        $response->assertStatus(200);

        // 全て正常な値で入力
        $response = $this->actingAs($admin)->post(route('admin.attendance.update', $attendance), [
            'clock_in_at' => '10:00',
            'clock_out_at' => '19:00',
            'breaks' => [
                [
                    'break_start_at' => '13:00',
                    'break_end_at' => '14:00',
                ],
            ],
            'note' => '修正更新テスト',
        ]);

        $response->assertStatus(302);

        // バリデーションエラーがないことを確認
        $response->assertSessionHasNoErrors();

        // 勤怠情報が更新されていることを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $staff->id,
            'work_date' => '2026-05-01',
            'clock_in_at' => '2026-05-01 10:00:00',
            'clock_out_at' => '2026-05-01 19:00:00',
            'note' => '修正更新テスト',
        ]);

        // 休憩情報が更新されていることを確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-05-01 13:00:00',
            'break_end_at' => '2026-05-01 14:00:00',
        ]);
    }
}
