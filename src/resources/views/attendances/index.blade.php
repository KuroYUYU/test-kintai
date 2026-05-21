@extends('layouts.app')

@section('css')
	<link rel="stylesheet" href="{{ asset('css/attendances/index.css') }}">
@endsection

@section('content')
<div class="index">
    <h1 class="index__title">勤怠一覧</h1>

    {{-- 月表示切り替えナビ --}}
    <div class="attendance-list__month-card">
        <div class="index__month-nav">
            <a href="{{ route('attendance.index', ['month' => $previousMonth]) }}">
                <img src="{{ asset('images/left.svg') }}" alt="前月" class="index__month-arrow">
                <span>前月</span>
            </a>

            <div class="index__month-label">
                <img src="{{ asset('images/calendar.svg') }}" alt="カレンダー" class="attendance__calendar-icon">
                <span>{{ $monthLabel }}</span>
            </div>

            <a href="{{ route('attendance.index', ['month' => $nextMonth]) }}">
                <span>翌月</span>
                <img src="{{ asset('images/right.svg') }}" alt="翌月" class="index__month-arrow">
            </a>
        </div>
    </div>

    {{-- テーブル --}}
    <table class="index__table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($days as $day)
            @php
                $date = $day->toDateString();
                $attendance = $attendances->get($date);

                // 休憩時間の合計
                $totalBreakMinutes = 0;

                if ($attendance) {
                    foreach ($attendance->breaks as $break) {
                        if ($break->break_start_at && $break->break_end_at) {
                            $start = \Carbon\Carbon::parse($break->break_start_at);
                            $end = \Carbon\Carbon::parse($break->break_end_at);

                            $totalBreakMinutes += $start->diffInMinutes($end);
                        }
                    }
                }

                $breakHours = floor($totalBreakMinutes / 60);
                $breakMinutes = $totalBreakMinutes % 60;

                // その日の実働時間(勤務時間 - 休憩時間)
                $totalWorkMinutes = null;

                if ($attendance && $attendance->clock_in_at && $attendance->clock_out_at) {
                    $clockIn = \Carbon\Carbon::parse($attendance->clock_in_at);
                    $clockOut = \Carbon\Carbon::parse($attendance->clock_out_at);

                    $workedMinutes = $clockIn->diffInMinutes($clockOut);

                    $totalWorkMinutes = $workedMinutes - $totalBreakMinutes;
                }

                $workHours = $totalWorkMinutes !== null ? floor($totalWorkMinutes / 60) : null;
                $workMinutes = $totalWorkMinutes !== null ? $totalWorkMinutes % 60 : null;
            @endphp

                <tr>
                    {{-- 日付 --}}
                    <td>{{ $day->isoFormat('MM/DD(ddd)') }}</td>

                    {{-- 出勤時間・退勤時間 --}}
                    <td>
                        {{ $attendance?->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '' }}
                    </td>

                    <td>
                        {{ $attendance?->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '' }}
                    </td>

                    {{-- 休憩時間 --}}
                    <td>
                        @if ($totalBreakMinutes > 0)
                            {{ $breakHours }}:{{ str_pad($breakMinutes, 2, '0', STR_PAD_LEFT) }}
                        @endif
                    </td>

                    {{-- 合計 --}}
                    <td>
                        @if (!is_null($totalWorkMinutes))
                            {{ $workHours }}:{{ str_pad($workMinutes, 2, '0', STR_PAD_LEFT) }}
                        @endif
                    </td>

                    {{-- 詳細 --}}
                    <td>
                        @if ($attendance)
                            <a href="{{ route('attendance.detail', $attendance->id) }}">詳細</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection