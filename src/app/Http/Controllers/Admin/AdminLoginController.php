<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request)
    {
        if (Auth::attempt($request->only('email', 'password'))){
            $request->session()->regenerate();

            // ロール判定
            if (auth()->user()->role === User::ROLE_ADMIN) {
                return redirect()->route('admin.attendance.index');
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