<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AttendanceListTest extends TestCase
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

    // 9 : 勤怠一覧情報取得機能（一般ユーザー）

    // Case1 自分が行った勤怠情報が全て表示されている
    public function test_my_attendance_list()
    {
        $user = $this->createStaff();

        // 二日分の休憩入り勤怠を作成
        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-01',
            'clock_in_at' => '2026-05-01 09:00:00',
            'clock_out_at' => '2026-05-01 18:00:00',
            'status' => Attendance::STATUS_DONE,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance1->id,
            'break_start_at' => '2026-05-01 12:00:00',
            'break_end_at' => '2026-05-01 13:00:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-02',
            'clock_in_at' => '2026-05-02 07:00:00',
            'clock_out_at' => '2026-05-02 13:00:00',
            'status' => Attendance::STATUS_DONE,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => '2026-05-02 11:00:00',
            'break_end_at' => '2026-05-02 11:30:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 5/1の勤怠
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');

        // 5/2の勤怠
        $response->assertSee('07:00');
        $response->assertSee('13:00');
        $response->assertSee('0:30');
    }

    // Case2 勤怠一覧画面に遷移した際に現在の月が表示される
    public function test_current_month_is_displayed()
    {
        // 日時は固定しておく
        Carbon::setTestNow(Carbon::parse('2026-05-01 10:00:00'));

        $user = $this->createStaff();

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 5月なので下記が期待値となる
        $response->assertSee('2026/05');

        Carbon::setTestNow();
    }

    // Case3 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_previous_month_is_displayed()
    {
        // 日時は固定しておく
        Carbon::setTestNow(Carbon::parse('2026-05-01 10:00:00'));

        $user = $this->createStaff();

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 今月の表示を確認
        $response->assertSee('2026/05');

        // 前月を指定し前月に切り替える
        $previousMonth = now()->subMonth()->format('Y-m');

        $response = $this->actingAs($user)->get(route('attendance.index', ['month' => $previousMonth]));

        // 前月なので4月の表示を確認
        $response->assertSee('2026/04');

        Carbon::setTestNow();
    }

    // Case4 「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_next_month_is_displayed()
    {
        // 日時は固定しておく
        Carbon::setTestNow(Carbon::parse('2026-05-01 10:00:00'));

        $user = $this->createStaff();

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 今月の表示を確認
        $response->assertSee('2026/05');

        // 翌月を指定し翌月に切り替える
        $nextMonth = now()->addMonth()->format('Y-m');

        $response = $this->actingAs($user)->get(route('attendance.index', ['month' => $nextMonth]));

        // 翌月なので6月の表示を確認
        $response->assertSee('2026/06');

        Carbon::setTestNow();
    }

    // Case5 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_my_attendance_detail()
    {
        $user = $this->createStaff();

        // 勤怠を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-01',
            'clock_in_at' => '2026-05-01 09:00:00',
            'clock_out_at' => '2026-05-01 18:00:00',
            'status' => Attendance::STATUS_DONE,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 一覧で詳細が表示されていることも確認
        $response->assertSee('詳細');

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance));

        $response->assertStatus(200);

        // 詳細画面での表示も確認
        $response->assertSee('2026年');
        $response->assertSee('5月1日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}
