@extends('layouts.app')

@section('css')
	<link rel="stylesheet" href="{{ asset('css/attendances/detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    {{-- 修正申請があればその内容を表示なければ修正前の勤怠 --}}
    @php
        if ($pendingCorrection){
            $displayClockIn = $pendingCorrection->requested_clock_in_at;
            $displayClockOut = $pendingCorrection->requested_clock_out_at;
            $displayNote = $pendingCorrection->requested_note;
            $displayBreaks = $pendingCorrection->correctionBreaks;
            $breakCount = $displayBreaks->count();
            $displayBreakCount = $breakCount;
        } else {
            $displayClockIn = $attendance->clock_in_at;
            $displayClockOut = $attendance->clock_out_at;
            $displayNote = $attendance->note;
            $displayBreaks = $attendance->breaks;
            $breakCount = $attendance->breaks->count();
            $displayBreakCount = $breakCount + 1;
        }
    @endphp

    @if ($pendingCorrection || $approvedCorrection)
        <div class="attendance-detail__table">
            <div class="attendance-detail__row">
                <div class="attendance-detail__label">名前</div>
                <div class="attendance-detail__value">
                    {{ $attendance->user->name ?? '' }}
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">日付</div>

                <div class="attendance-detail__value attendance-detail__value--date">
                    <div class="attendance-detail__date-grid">
                        <span class="attendance-detail__date-year">
                            {{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}
                        </span>
                        <span></span>
                        <span class="attendance-detail__date-md">
                            {{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">出勤・退勤</div>

                <div class="attendance-detail__value">
                    <div class="attendance-detail__time-texts">
                        <span>{{ $displayClockIn ? \Carbon\Carbon::parse($displayClockIn)->format('H:i') : '' }}</span>
                        <span>〜</span>
                        <span>{{ $displayClockOut ? \Carbon\Carbon::parse($displayClockOut)->format('H:i') : '' }}</span>
                    </div>
                </div>
            </div>

            @for ($i = 0; $i < $displayBreakCount; $i++)
                @php
                    $break = $displayBreaks->get($i);

                    $breakStart = '';
                    $breakEnd = '';

                    if ($break && $break->break_start_at) {
                        $breakStart = \Carbon\Carbon::parse($break->break_start_at)->format('H:i');
                    }

                    if ($break && $break->break_end_at) {
                        $breakEnd = \Carbon\Carbon::parse($break->break_end_at)->format('H:i');
                    }
                @endphp

                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">
                        @if ($i === 0)
                            休憩
                        @else
                            休憩{{ $i + 1 }}
                        @endif
                    </div>

                    <div class="attendance-detail__value">
                        <div class="attendance-detail__time-texts">
                            <span>{{ $breakStart }}</span>
                            <span>〜</span>
                            <span>{{ $breakEnd }}</span>
                        </div>
                    </div>
                </div>
            @endfor

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">備考</div>

                <div class="attendance-detail__value">
                    <span>{{ $displayNote }}</span>
                </div>
            </div>
        </div>

        @if ($pendingCorrection)
            <p class="attendance-detail__pending-message">
                *承認待ちのため修正できません。
            </p>
        @endif
    @else
        <form action="{{ route('stamp_correction_request.store', $attendance) }}" method="POST">
            @csrf
            <div class="attendance-detail__table">
                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">名前</div>

                    <div class="attendance-detail__value">
                        {{ $attendance->user->name ?? '' }}
                    </div>
                </div>

                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">日付</div>

                    <div class="attendance-detail__value attendance-detail__value--date">
                        <div class="attendance-detail__date-grid">
                            <span class="attendance-detail__date-year">
                                {{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}
                            </span>
                            {{-- CSSでgridで揃えるため下記記載 --}}
                            <span></span>
                            <span class="attendance-detail__date-md">
                                {{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">出勤・退勤</div>

                    <div class="attendance-detail__value">
                        <div class="attendance-detail__time-inputs">
                            <input type="text" name="clock_in_at" value="{{ old('clock_in_at', $displayClockIn ? \Carbon\Carbon::parse($displayClockIn)->format('H:i') : '') }}">
                            <span>〜</span>
                            <input type="text" name="clock_out_at" value="{{ old('clock_out_at', $displayClockOut ? \Carbon\Carbon::parse($displayClockOut)->format('H:i') : '') }}">
                        </div>

                        @error('clock_in_at')
                            <p class="attendance-detail__error">{{ $message }}</p>
                        @enderror

                        @error('clock_out_at')
                            <p class="attendance-detail__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @for ($i = 0; $i < $displayBreakCount; $i++)
                    @php
                        $break = $displayBreaks->get($i);

                        // 休憩データがない場合もあるので最初は空文字
                        $breakStart = '';
                        $breakEnd = '';

                        // 休憩データが存在かつ休憩開始時刻もあるならH:i形式に整えて$breakStartに入れる
                        if ($break && $break->break_start_at) {
                            $breakStart = \Carbon\Carbon::parse($break->break_start_at)->format('H:i');
                        }

                        // 休憩データが存在かつ休憩終了時刻もあるならH:i形式に整えて$breakEndに入れる
                        if ($break && $break->break_end_at) {
                            $breakEnd = \Carbon\Carbon::parse($break->break_end_at)->format('H:i');
                        }
                    @endphp

                    <div class="attendance-detail__row">
                        <div class="attendance-detail__label">
                            @if ($i === 0)
                                休憩
                            @else
                                休憩{{ $i + 1 }}
                            @endif
                        </div>

                        <div class="attendance-detail__value">
                            <div class="attendance-detail__time-inputs">
                                <input type="text" name="breaks[{{ $i }}][break_start_at]" value="{{ old("breaks.$i.break_start_at", $breakStart) }}">
                                <span>〜</span>
                                <input type="text" name="breaks[{{ $i }}][break_end_at]" value="{{ old("breaks.$i.break_end_at", $breakEnd) }}">
                            </div>

                            @error("breaks.$i.break_start_at")
                                <p class="attendance-detail__error">{{ $message }}</p>
                            @enderror

                            @error("breaks.$i.break_end_at")
                                <p class="attendance-detail__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endfor

                <div class="attendance-detail__row">
                    <div class="attendance-detail__label">備考</div>

                    <div class="attendance-detail__value">
                        <textarea name="requested_note">{{ old('requested_note', $displayNote ?? '') }}</textarea>

                        @error("requested_note")
                            <p class="attendance-detail__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="attendance-detail__actions">
                <button type="submit">修正</button>
            </div>
        </form>
    @endif
</div>
@endsection