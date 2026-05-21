<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CorrectionBreak;

class AttendanceCorrectionController extends Controller
{
    public function store(AttendanceCorrectionRequest $request, Attendance $attendance)
    {
        $correction = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'requested_by' => auth()->id(),
            'approved_by' => null,
            'status' => AttendanceCorrection::STATUS_PENDING,
            'requested_clock_in_at' => $attendance->work_date . ' ' . $request->clock_in_at,
            'requested_clock_out_at' => $attendance->work_date . ' ' . $request->clock_out_at,
            'requested_note' => $request->requested_note,
        ]);

        // 休憩を保存 (開始〜終了が空の行は保存しない)
        foreach ($request->breaks as $break) {
            if (empty($break['break_start_at']) && empty($break['break_end_at'])) {
                continue;
            }

            CorrectionBreak::create([
                'attendance_correction_id' => $correction->id,
                'break_start_at' => $attendance->work_date . ' ' . $break['break_start_at'],
                'break_end_at' => $attendance->work_date . ' ' . $break['break_end_at'],
            ]);
        }

        return redirect()->route('attendance.detail', $attendance->id);
    }

    public function index(Request $request)
    {
        $page = $request->query('page', 'pending');

        if ($page === 'approved') {
            $status = AttendanceCorrection::STATUS_APPROVED;
        } else {
            $status = AttendanceCorrection::STATUS_PENDING;
        }

        $corrections = AttendanceCorrection::where('requested_by', auth()->id())
            ->where('status', $status)
            ->with(['attendance.user'])
            ->latest()
            ->get();

        return view('stamp_correction_request.index', compact('corrections', 'page'));
    }
}
