<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <div class="header__brand">
                <img class="header__brand-logo" src="{{ asset('images/logo.png') }}" alt="COACHTECH">
            </div>

            @auth
                @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                    {{-- 管理者用ヘッダーナビ --}}
                    <nav class="header__nav">
                        <a href="{{ route('admin.attendance.index') }}" class="header__link">
                            勤怠一覧
                        </a>

                        <a href="{{ route('admin.staff.index') }}" class="header__link">
                            スタッフ一覧
                        </a>

                        <a href="{{ route('admin.stamp_correction_request.index') }}" class="header__link">
                            申請一覧
                        </a>

                        <form action="{{ route('logout') }}" method="post" class="header__form">
                            @csrf
                            <button type="submit" class="header__button">ログアウト</button>
                        </form>
                    </nav>
                @else
                    {{-- スタッフ用ヘッダーナビ --}}
                    <nav class="header__nav">
                        <a href="{{ route('attendance.dashboard') }}" class="header__link">
                            勤怠
                        </a>

                        <a href="{{ route('attendance.index') }}" class="header__link">
                            勤怠一覧
                        </a>

                        <a href="{{ route('stamp_correction_request.index') }}" class="header__link">
                            申請
                        </a>

                        <form action="{{ route('logout') }}" method="post" class="header__form">
                            @csrf
                            <button type="submit" class="header__button">ログアウト</button>
                        </form>
                    </nav>
                @endif
            @endauth
        </div>
    </header>

	<main class="content">
		@yield('content')
	</main>
</body>
</html>