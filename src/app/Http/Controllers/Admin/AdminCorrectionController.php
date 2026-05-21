<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCorrectionController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->query('page', 'pending');

        if ($page === 'approved') {
            $status = AttendanceCorrection::STATUS_APPROVED;
        } else {
            $status = AttendanceCorrection::STATUS_PENDING;
        }

        // 管理者は全員分の申請を表示させる
        $corrections = AttendanceCorrection::where('status', $status)
            ->with(['attendance.user'])
            ->latest()
            ->get();

        return view('admin.stamp_correction_request.index', compact('page', 'corrections'));
    }

    // 修正申請承認
    public function approve(AttendanceCorrection $attendance_correction)
    {
        DB::transaction(function () use ($attendance_correction) {

            // 修正申請に紐づく基の勤怠を取得し修正申請の内容に更新
            $attendance = $attendance_correction->attendance;

            $attendance->update([
                'clock_in_at' => $attendance_correction->requested_clock_in_at,
                'clock_out_at' => $attendance_correction->requested_clock_out_at,
                'note' => $attendance_correction->requested_note,
            ]);

            // 休憩は基のを一度すべて削除し修正申請の内容で新たに作る
            $attendance->breaks()->delete();

            foreach ($attendance_correction->correctionBreaks as $correctionBreak) {
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $correctionBreak->break_start_at,
                    'break_end_at' => $correctionBreak->break_end_at,
                ]);
            }

            // 修正申請のステータスを承認済みにする
            $attendance_correction->update([
                'status' => AttendanceCorrection::STATUS_APPROVED,
                'approved_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.attendance.detail', $attendance_correction->attendance_id);
    }
}
