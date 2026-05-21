@extends('layouts.auth')

@section('css')
	<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
<div class="register">
    <div class="register__header">
        <h1 class="register__title">会員登録</h1>
    </div>

    <div class="register__card">
        <form action="" class="register__form" method="post">
            @csrf
            {{-- ユーザー名 --}}
            <div class="register__group">
                <label class="register__label" for="name">
                    名前
                </label>
                <input class="register__input" id="name" type="text" name="name" value="{{ old('name') }}">
                @error('name')
                    <p class="register__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- メールアドレス --}}
            <div class="register__group">
                <label class="register__label" for="email">
                    メールアドレス
                </label>
                <input class="register__input" id="email" type="text" name="email" value="{{ old('email') }}">
                @error('email')
                    <p class="register__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- パスワード --}}
            <div class="register__group">
                <label class="register__label" for="password">
                    パスワード
                </label>
                <input class="register__input" id="password" type="password" name="password" autocomplete="new-password">
                @error('password')
                    <p class="register__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- パスワード確認 --}}
            <div class="register__group">
                <label class="register__label" for="password_confirmation">
                    パスワード確認
                </label>
                <input class="register__input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
            </div>

            {{-- 下部ボタン --}}
            <div class="register__actions">
                <button type="submit" class="register__button">登録する</button>
                <a href="{{ route('login') }}" class="login-back__button">ログインはこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection