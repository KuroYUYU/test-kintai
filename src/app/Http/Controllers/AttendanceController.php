<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceController extends Controller
{
    public function dashboard()
    {
        $userId = auth()->id();
        $today  = now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)->where('work_date', $today)->first();

        $status = $attendance?->status ?? Attendance::STATUS_OFF;

        // 画面表示用 日付と時刻
        $todayLabel = now()->isoFormat('YYYY年M月D日(ddd)');
        $timeLabel  = now()->format('H:i');

        // 画面表示用 バッジ文言
        $clockInAt  = $attendance?->clock_in_at;
        $clockOutAt = $attendance?->clock_out_at;

        if ($clockOutAt) {
            $badge = '退勤済';
        } elseif (! $clockInAt) {
            $badge = '勤務外';
        } elseif ($status === Attendance::STATUS_BREAKING) {
            $badge = '休憩中';
        } else {
            $badge = '出勤中';
        }

        return view('attendances.attendance', compact('attendance', 'status', 'todayLabel', 'timeLabel', 'badge'));
    }

    // 出勤
    public function clockIn()
    {
        $userId = auth()->id();
        $today  = now()->toDateString();

        // 今日の勤怠が無ければ作る（あれば取得）
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $userId, 'work_date' => $today],
            ['status' => Attendance::STATUS_OFF]
        );

        // すでに出勤済みなら何もしない（要件：1日1回）
        if ($attendance->clock_in_at !== null) {
            return redirect()->route('attendance.dashboard');
        }

        $attendance->update([
            'clock_in_at' => now(),
            'status'      => Attendance::STATUS_WORKING,
        ]);

        return redirect()->route('attendance.dashboard');
    }

    // 休憩
    public function breakStart()
    {
        $userId = auth()->id();
        $today  = now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)->where('work_date', $today)->firstOrFail();

        // すでに休憩中なら戻す
        if ($attendance->breaks()->whereNull('break_end_at')->exists()) {
            return redirect()->route('attendance.dashboard');
        }

        DB::transaction(function () use ($attendance) {
            // 休憩レコード作成
            $attendance->breaks()->create([
                'break_start_at' => now(),
                'break_end_at'   => null,
            ]);

            // 状態を休憩中に
            $attendance->update([
                'status' => Attendance::STATUS_BREAKING,
            ]);
        });

        return redirect()->route('attendance.dashboard');
    }

    // 休憩終了
    public function breakEnd()
    {
        $userId = auth()->id();
        $today  = now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)->where('work_date', $today)->firstOrFail();

        // 休憩中のレコードが「必ずある」前提（なければ 404）
        $activeBreak = $attendance->breaks()->whereNull('break_end_at')->firstOrFail();

        DB::transaction(function () use ($attendance, $activeBreak) {
            $activeBreak->update([
                'break_end_at' => now(),
            ]);

            // 状態を出勤中に
            $attendance->update([
                'status' => Attendance::STATUS_WORKING,
            ]);
        });

        return redirect()->route('attendance.dashboard');
    }

    // 退勤
    public function clockOut()
    {
        $userId = auth()->id();
        $today  = now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)->where('work_date', $today)->firstOrFail();

        // すでに退勤済みなら何もしない(二重送信などの事故防止)
        if ($attendance->clock_out_at !== null) {
            return redirect()->route('attendance.dashboard');
        }

        // UIでは休憩中に退勤ボタンは非表示だが念のためチェック判定
        if ($attendance->breaks()->whereNull('break_end_at')->exists()) {
            return redirect()->route('attendance.dashboard');
        }

        $attendance->update([
            'clock_out_at' => now(),
            // 状態を退勤済みに
            'status'       => Attendance::STATUS_DONE,
        ]);

        return redirect()->route('attendance.dashboard');
    }

    // 勤怠一覧
    public function index(Request $request)
    {
        $userId = auth()->id();

        if ($request->filled('month')) {
            $month = Carbon::parse($request->month)->startOfMonth();
        } else {
            $month = now()->startOfMonth();
        }

        // テーブルで1ヶ月分の日付行を作る
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();
        $days  = CarbonPeriod::create($start, $end);

        // 月ナビ用：表示月・前月・翌月を作る
        $monthLabel = $month->format('Y/m');
        $previousMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        $attendances = Attendance::where('user_id', $userId)->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])->with('breaks')->get()->keyBy('work_date');

        return view('attendances.index', compact('days', 'monthLabel', 'attendances', 'previousMonth', 'nextMonth',));
    }

    // 勤怠詳細
    public function detail(Attendance $attendance)
    {
        $attendance->load('user', 'breaks');

        $pendingCorrection = $attendance->corrections()
            ->with('correctionBreaks')
            ->where('status', AttendanceCorrection::STATUS_PENDING)
            ->first();

        $approvedCorrection = $attendance->corrections()
            ->where('status', AttendanceCorrection::STATUS_APPROVED)
            ->latest()
            ->first();

        return view('attendances.detail', compact('attendance', 'pendingCorrection', 'approvedCorrection'));
    }
}
