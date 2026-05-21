@extends('layouts.app')

@section('css')
	<link rel="stylesheet" href="{{ asset('css/admin/stamp_correction_request/index.css') }}">
@endsection

@section('content')
<div class="index">
    <h1 class="index__title">申請一覧</h1>

    <div class="index__tabs">
        <a href="{{ route('admin.stamp_correction_request.index', ['page' => 'pending']) }}" class="index__tab @if ($page === 'pending') index__tab--active @endif">承認待ち</a>

        <a href="{{ route('admin.stamp_correction_request.index', ['page' => 'approved']) }}" class="index__tab @if ($page === 'approved') index__tab--active @endif">承認済み</a>
    </div>

    <table class="index__table">
        <thead>
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($corrections as $correction)
                <tr>
                    <td>
                        @if ($correction->status === 0)
                            承認待ち
                        @elseif ($correction->status === 1)
                            承認済み
                        @endif
                    </td>

                    <td>
                        {{ $correction->attendance->user->name }}
                    </td>

                    <td>
                        {{ \Carbon\Carbon::parse($correction->attendance->work_date)->format('Y/m/d') }}
                    </td>

                    <td>
                        {{ $correction->requested_note }}
                    </td>

                    <td>
                        {{ $correction->created_at->format('Y/m/d') }}
                    </td>

                    <td>
                        <a href="{{ route('admin.attendance.detail', $correction->attendance_id) }}">
                            詳細
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection