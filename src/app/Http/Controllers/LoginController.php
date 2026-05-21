<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(LoginRequest $request)
    {
        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();

            // ロール判定
            if (auth()->user()->role === User::ROLE_STAFF) {
                return redirect()->route('attendance.dashboard');
            }

            Auth::logout();

            // 失敗時：auth.php の failed を使う
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }
}
