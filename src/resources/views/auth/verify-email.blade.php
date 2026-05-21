@extends('layouts.auth')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email">
    <p class="verify-email__text">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    <a class="verify-email__main-button" href="http://localhost:8025" target="_blank" rel="noopener noreferrer">
    認証はこちらから
    </a>

    <form class="verify-email__resend-form" method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="verify-email__resend-link" type="submit">
            認証メールを再送する
        </button>
    </form>
</div>
@endsection