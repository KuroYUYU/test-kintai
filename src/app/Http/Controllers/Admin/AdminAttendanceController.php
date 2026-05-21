<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AdminAttendanceUpdateRequest;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->query('date')) {
            $date = Carbon::parse($request->query('date'));
        } else {
            $date = today();
        }

        // 日付タイトルのフォーマット
        $titleDateLabel = $date->format('Y年n月j日');
        // 日付ナビのフォーマット
        $navDateLabel = $date->format('Y/m/d');
        $previousDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();

        $attendances = Attendance::whereDate('work_date', $date->toDateString())
            ->with(['user', 'breaks'])->get();

        return view('admin.attendances.index', compact('date', 'titleDateLabel', 'navDateLabel', 'attendances', 'previousDate', 'nextDate'));
    }

    // 詳細
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

        return view('admin.attendances.detail', compact('attendance', 'pendingCorrection', 'approvedCorrection'));
    }

    // 月次勤怠一覧
    public function staff(Request $request, User $user)
    {
        $userId = $user->id;

        if ($request->filled('month')) {
            $month = Carbon::parse($request->month)->startOfMonth();
        } else {
            $month = now()->startOfMonth();
        }

        // 月ナビ用：表示月・前月・翌月を作る
        $monthLabel = $month->format('Y/m');
        $previousMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        // テーブルで1ヶ月分の日付行を作る
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();
        $days  = CarbonPeriod::create($start, $end);

        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->with('breaks')
            ->get()
            ->keyBy('work_date');

        return view('admin.attendances.staff', compact('user', 'monthLabel', 'previousMonth', 'nextMonth', 'attendances', 'days', 'month'));
    }

    // 勤怠修正
    public function update(AdminAttendanceUpdateRequest $request, Attendance $attendance)
    {
        DB::transaction(function () use ($request, $attendance) {
            $workDate = Carbon::parse($attendance->work_date)->toDateString();

            $attendance->update([
                'clock_in_at' => $workDate . ' ' . $request->clock_in_at,
                'clock_out_at' => $workDate . ' ' . $request->clock_out_at,
                'note' => $request->note,
            ]);

            $attendance->breaks()->delete();

            foreach ($request->input('breaks', []) as $break) {
                if (empty($break['break_start_at']) && empty($break['break_end_at'])) {
                    continue;
                }

                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $workDate . ' ' . $break['break_start_at'],
                    'break_end_at' => $workDate . ' ' . $break['break_end_at'],
                ]);
            }
        });

        return redirect()->route('admin.attendance.detail', $attendance->id);
    }

    // CSV出力
    public function exportCsv(Request $request, User $user)
    {
        if ($request->filled('month')) {
            $month = Carbon::parse($request->month)->startOfMonth();
        } else {
            $month = now()->startOfMonth();
        }

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $days = CarbonPeriod::create($start, $end);

        $attendances = Attendance::where('user_id', $user->id)->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->with('breaks')
            ->get()
            ->keyBy('work_date');

        // ファイル名を作る
        $fileName = $user->name . '_' . $month->format('Y-m') . '_attendance.csv';

        // ダウンロード用ヘッダーを作る
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        // CSVの中身
        $callback = function () use ($days, $attendances) {
            $file = fopen('php://output', 'w');

            // Excel文字化け対策
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($days as $day) {
                $date = $day->toDateString();
                $attendance = $attendances->get($date);

                $clockIn = '';
                $clockOut = '';
                $breakTime = '';
                $workTime = '';

                $totalBreakMinutes = 0;

                if ($attendance) {
                    if ($attendance->clock_in_at) {
                        $clockIn = Carbon::parse($attendance->clock_in_at)->format('H:i');
                    }

                    if ($attendance->clock_out_at) {
                        $clockOut = Carbon::parse($attendance->clock_out_at)->format('H:i');
                    }

                    foreach ($attendance->breaks as $break) {
                        if ($break->break_start_at && $break->break_end_at) {
                            $breakStart = Carbon::parse($break->break_start_at);
                            $breakEnd = Carbon::parse($break->break_end_at);

                            $totalBreakMinutes += $breakStart->diffInMinutes($breakEnd);
                        }
                    }

                    if ($totalBreakMinutes > 0) {
                        $breakHours = floor($totalBreakMinutes / 60);
                        $breakMinutes = $totalBreakMinutes % 60;
                        $breakTime = $breakHours . ':' . str_pad($breakMinutes, 2, '0', STR_PAD_LEFT);
                    }

                    if ($attendance->clock_in_at && $attendance->clock_out_at) {
                        $workedMinutes = Carbon::parse($attendance->clock_in_at)
                            ->diffInMinutes(Carbon::parse($attendance->clock_out_at));

                        $totalWorkMinutes = $workedMinutes - $totalBreakMinutes;

                        $workHours = floor($totalWorkMinutes / 60);
                        $workMinutes = $totalWorkMinutes % 60;
                        $workTime = $workHours . ':' . str_pad($workMinutes, 2, '0', STR_PAD_LEFT);
                    }
                }

                fputcsv($file, [
                    $day->locale('ja')->isoFormat('MM/DD(ddd)'),
                    $clockIn,
                    $clockOut,
                    $breakTime,
                    $workTime,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
