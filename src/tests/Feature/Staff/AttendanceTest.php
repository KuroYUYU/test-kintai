<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceBreak;


class AttendanceTest extends TestCase
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

    // 4 : 日時取得機能

    // Case1 現在の日時情報がUIと同じ形式で出力されている
    public function test_current_datetime_is_displayed()
    {
        // テスト時の日付と時刻は固定しておく
        Carbon::setTestNow(Carbon::parse('2026-05-01 08:00:00'));

        $user = $this->createStaff();

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        $response->assertSee('2026年5月1日(金)');

        $response->assertSee('08:00');

        // テスト後固定日時を解除
        Carbon::setTestNow();
    }

    // 5 : ステータス確認機能

    // Case1 勤務外の場合、勤怠ステータスが正しく表示される
    public function test_off_work_status_is_displayed()
    {
        $user = $this->createStaff();

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        $response->assertSee('勤務外');
    }

    // Case2 出勤中の場合、勤怠ステータスが正しく表示される
    public function test_working_status_is_displayed()
    {
        $user = $this->createStaff();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_WORKING,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        $response->assertSee('出勤中');
    }

    // Case3 休憩中の場合、勤怠ステータスが正しく表示される
    public function test_break_status_is_displayed()
    {
        $user = $this->createStaff();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_BREAKING,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        $response->assertSee('休憩中');
    }

    // Case4 退勤済みの場合、勤怠ステータスが正しく表示される
    public function test_done_status_is_displayed()
    {
        $user = $this->createStaff();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => now()->setTime(16, 0),
            'status' => Attendance::STATUS_DONE,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        $response->assertSee('退勤済');
    }

    // 6 : 出勤機能

    // Case1 出勤ボタンが正しく機能する
    // ※テストケースの期待動作に「勤務中」になるとあるが、正しくは「出勤中」のため「出勤中」で作成している
    public function test_clock_in_button_check()
    {
        $user = $this->createStaff();

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        // 出勤ボタンが表示されていることを確認
        $response->assertSee('出勤');

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.dashboard'));

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        // 勤務中になっていることを確認
        $response->assertSee('出勤中');
    }

    // Case2 出勤は一日一回のみできる
    public function test_clock_in_once_per_day()
    {
        $user = $this->createStaff();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => now()->setTime(16, 0),
            'status' => Attendance::STATUS_DONE,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        // 出勤が表示されないことを確認
        $response->assertDontSee('出勤');
    }

    // Case3 出勤時刻が勤怠一覧画面で確認できる
    public function test_clock_in_time_check_list()
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 08:00:00'));

        $user = $this->createStaff();

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.dashboard'));

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        $response->assertSee('08:00');

        Carbon::setTestNow();
    }

    // 7 : 休憩機能

    // Case1 休憩ボタンが正しく機能する
    public function test_break_start_button_check()
    {
        $user = $this->createStaff();

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.dashboard'));

        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        $response->assertSee('休憩入');

        $response = $this->actingAs($user)->post(route('attendance.breakStart'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 休憩開始後の勤怠画面を開く
        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        // 休憩中になっていることを確認
        $response->assertSee('休憩中');
    }

    // Case2 休憩は一日に何回でもできる
    public function test_break_can_many_times()
    {
        $user = $this->createStaff();

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 休憩入り
        $response = $this->actingAs($user)->post(route('attendance.breakStart'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 休憩戻
        $response = $this->actingAs($user)->post(route('attendance.breakEnd'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 戻り後の勤怠画面を開く
        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        // もう一度「休憩入」ボタンが表示されている
        $response->assertSee('休憩入');
    }

    // Case3 休憩戻ボタンが正しく機能する
    public function test_break_end_button()
    {
        $user = $this->createStaff();

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 休憩入り
        $response = $this->actingAs($user)->post(route('attendance.breakStart'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 休憩戻
        $response = $this->actingAs($user)->post(route('attendance.breakEnd'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 戻り後の勤怠画面を開く
        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        // 出勤中に戻っていることを確認
        $response->assertSee('出勤中');
    }

    // Case4 休憩戻は一日に何回でもできる
    public function test_break_end_can_many_times()
    {
        $user = $this->createStaff();

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 休憩入り
        $response = $this->actingAs($user)->post(route('attendance.breakStart'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 休憩戻
        $response = $this->actingAs($user)->post(route('attendance.breakEnd'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 戻り後の勤怠画面を開く
        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        // 再度休憩入り
        $response = $this->actingAs($user)->post(route('attendance.breakStart'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 2回目の休憩中の画面を開く
        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        // 再度「休憩戻」ボタンが表示されている
        $response->assertSee('休憩戻');
    }

    // Case5 休憩時刻が勤怠一覧画面で確認できる
    public function test_break_time_on_index()
    {
        $user = $this->createStaff();

        // 09:00に出勤したとする
        Carbon::setTestNow(Carbon::parse('2026-05-01 09:00:00'));

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 12:00に休憩入したとする
        Carbon::setTestNow(Carbon::parse('2026-05-01 12:00:00'));

        $response = $this->actingAs($user)->post(route('attendance.breakStart'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 13:15に休憩戻したとする
        Carbon::setTestNow(Carbon::parse('2026-05-01 13:15:00'));

        $response = $this->actingAs($user)->post(route('attendance.breakEnd'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 勤怠一覧画面を開く
        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 一覧に休憩時間が表示されていることを確認
        $response->assertSee('1:15');

        Carbon::setTestNow();
    }

    // 8 : 退勤機能

    // Case1 退勤ボタンが正しく機能する
    public function test_clock_out_button_check()
    {
        $user = $this->createStaff();

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 勤怠画面を開く
        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        // 退勤ボタンが表示されている
        $response->assertSee('退勤');

        $response = $this->actingAs($user)->post(route('attendance.clockOut'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 勤怠画面を開く
        $response = $this->actingAs($user)->get(route('attendance.dashboard'));

        $response->assertStatus(200);

        // 退勤済が表示されている
        $response->assertSee('退勤済');
    }

    // Case2 退勤時刻が勤怠一覧画面で確認できる
    public function test_clock_out_time_on_index()
    {
        $user = $this->createStaff();

        // 09:00に出勤したとする
        Carbon::setTestNow(Carbon::parse('2026-05-01 09:00:00'));

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 13:30に退勤したとする
        Carbon::setTestNow(Carbon::parse('2026-05-01 13:30:00'));

        $response = $this->actingAs($user)->post(route('attendance.clockOut'));

        $response->assertRedirect(route('attendance.dashboard'));

        // 勤怠一覧画面を開く
        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 一覧に退勤時間が表示されていることを確認
        $response->assertSee('13:30');

        Carbon::setTestNow();
    }
}
