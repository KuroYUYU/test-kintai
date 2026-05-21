@extends('layouts.app')

@section('css')
	<link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('content')
<div class="login">
    <div class="login__header">
        <h1 class="login__title">管理者ログイン</h1>
    </div>

    <div class="login__card">
        <form action="{{ route('admin.login.store') }}" class="login__form" method="post">
            @csrf
            {{-- メールアドレス --}}
            <div class="login__group">
                <label class="login__label" for="email">
                    メールアドレス
                </label>
                <input class="login__input" id="email" type="text" name="email" value="{{ old('email') }}">
                @error('email')
                    <p class="login__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- パスワード --}}
            <div class="login__group">
                <label class="login__label" for="password">
                    パスワード
                </label>
                <input class="login__input" id="password" type="password" name="password">
                @error('password')
                    <p class="login__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- 下部ボタン --}}
            <div class="login__actions">
                <button type="submit" class="login__button">管理者ログインする</button>
            </div>
        </form>
    </div>
</div>
@endsection