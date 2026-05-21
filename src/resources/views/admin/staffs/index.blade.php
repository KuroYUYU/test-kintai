@extends('layouts.app')

@section('css')
	<link rel="stylesheet" href="{{ asset('css/admin/staffs/index.css') }}">
@endsection

@section('content')
<div class="index">
    <h1 class="index__title">スタッフ一覧</h1>

    {{-- テーブル --}}
    <table class="index__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($staffs as $staff)
                <tr>
                    {{-- 名前 --}}
                    <td>{{ $staff->name }}</td>

                    {{-- メールアドレス --}}
                    <td>{{ $staff->email }}</td>

                    {{-- 月次勤怠 --}}
                    <td>
                        <a href="{{ route('admin.attendance.staff', $staff->id) }}">詳細</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection