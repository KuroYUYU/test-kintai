@extends('layouts.app')

@section('css')
	<link rel="stylesheet" href="{{ asset('css/attendances/attendance.css') }}">
@endsection

@section('content')
<div class="attendance">
    <div class="attendance__badge">{{ $badge }}</div>
    <p class="attendance__date">{{ $todayLabel }}</p>
    <p class="attendance__time">{{ $timeLabel }}</p>

    @php
        $clockInAt  = $attendance?->clock_in_at;
        $clockOutAt = $attendance?->clock_out_at;
    @endphp

    {{-- ボタン --}}
    <div class="attendance__actions">
        @if ($clockOutAt)
            <p class="attendance__done">お疲れ様でした。</p>
        @elseif (! $clockInAt)
            <form method="POST" action="{{ route('attendance.clockIn') }}">
                @csrf
                <button type="submit" class="attendance__btn">出勤</button>
            </form>
        @elseif ($status === \App\Models\Attendance::STATUS_WORKING)
            <div class="attendance__actions-row">
                <form method="POST" action="{{ route('attendance.clockOut') }}">
                    @csrf
                    <button type="submit" class="attendance__btn">退勤</button>
                </form>

                <form method="POST" action="{{ route('attendance.breakStart') }}">
                    @csrf
                    <button type="submit" class="attendance__blake-btn">休憩入</button>
                </form>
            </div>
        @elseif ($status === \App\Models\Attendance::STATUS_BREAKING)
            <form method="POST" action="{{ route('attendance.breakEnd') }}">
                @csrf
                <button type="submit" class="attendance__blake-btn">休憩戻</button>
            </form>
        @endif
    </div>
</div>
@endsection