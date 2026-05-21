@extends('layouts.app')

@section('css')
	<link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
@endsection

@section('content')
<div class="index">
    <h1 class="index__title">{{ $titleDateLabel }}の勤怠</h1>

    {{-- 日表示切り替えナビ --}}
    <div class="attendance-list__date-card">
        <div class="index__date-nav">
            <a href="{{ route('admin.attendance.index', ['date' => $previousDate]) }}">
                <img src="{{ asset('images/left.svg') }}" alt="前日" class="index__date-arrow">
                <span>前日</span>
            </a>

            <div class="index__date-label">
                <img src="{{ asset('images/calendar.svg') }}" alt="カレンダー" class="attendance__calendar-icon">
                <span>{{ $navDateLabel }}</span>
            </div>

            <a href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}">
                <span>翌日</span>
                <img src="{{ asset('images/right.svg') }}" alt="翌日" class="index__date-arrow">
            </a>
        </div>
    </div>

    {{-- テーブル --}}
    <table class="index__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($attendances as $attendance)
            @php
                // 休憩時間の合計
                $totalBreakMinutes = 0;

                foreach ($attendance->breaks as $break) {
                    if ($break->break_start_at && $break->break_end_at) {
                        $start = \Carbon\Carbon::parse($break->break_start_at);
                        $end = \Carbon\Carbon::parse($break->break_end_at);

                        $totalBreakMinutes += $start->diffInMinutes($end);
                    }
                }

                $breakHours = floor($totalBreakMinutes / 60);
                $breakMinutes = $totalBreakMinutes % 60;

                // その日の実働時間(勤務時間 - 休憩時間)
                $totalWorkMinutes = null;

                if ($attendance->clock_in_at && $attendance->clock_out_at) {
                    $clockIn = \Carbon\Carbon::parse($attendance->clock_in_at);
                    $clockOut = \Carbon\Carbon::parse($attendance->clock_out_at);

                    $workedMinutes = $clockIn->diffInMinutes($clockOut);

                    $totalWorkMinutes = $workedMinutes - $totalBreakMinutes;
                }

                $workHours = $totalWorkMinutes !== null ? floor($totalWorkMinutes / 60) : null;
                $workMinutes = $totalWorkMinutes !== null ? $totalWorkMinutes % 60 : null;
            @endphp

                <tr>
                    {{-- 名前 --}}
                    <td>{{ $attendance->user->name }}</td>

                    {{-- 出勤時間・退勤時間 --}}
                    <td>
                        @if ($attendance->clock_in_at)
                            {{ \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') }}
                        @endif
                    </td>

                    <td>
                        @if ($attendance->clock_out_at)
                            {{ \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') }}
                        @endif
                    </td>

                    {{-- 休憩時間 --}}
                    <td>
                        @if ($totalBreakMinutes > 0)
                            {{ $breakHours }}:{{ str_pad($breakMinutes, 2, '0', STR_PAD_LEFT) }}
                        @endif
                    </td>

                    {{-- 合計 --}}
                    <td>
                        @if ($totalWorkMinutes !== null)
                            {{ $workHours }}:{{ str_pad($workMinutes, 2, '0', STR_PAD_LEFT) }}
                        @endif
                    </td>

                    {{-- 詳細 --}}
                    <td>
                        <a href="{{ route('admin.attendance.detail', $attendance->id) }}">詳細</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection