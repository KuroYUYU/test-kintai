@extends('layouts.auth')

@section('css')
	<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login">
    <div class="login__header">
        <h1 class="login__title">ログイン</h1>
    </div>

    <div class="login__card">
        <form action="{{ route('login') }}" class="login__form" method="post">
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
                <button type="submit" class="login__button">ログインする</button>
                <a href="{{ route('register') }}" class="login__register-link">会員登録はこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection