<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AdminCorrectionController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ログインの際LoginRequestでバリデーションを使用するため設定
Route::post('/login', [LoginController::class, 'store'])->name('login');

// 出勤前
Route::get('/attendance', [AttendanceController::class, 'dashboard'])->middleware(['auth', 'verified'])->name('attendance.dashboard');

// 出勤中
Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->middleware(['auth', 'verified'])->name('attendance.clockIn');

// 休憩開始
Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->middleware(['auth', 'verified'])->name('attendance.breakStart');

// 休憩終了
Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->middleware(['auth', 'verified'])->name('attendance.breakEnd');

// 退勤
Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->middleware(['auth', 'verified'])->name('attendance.clockOut');

// 一般ユーザー一覧
Route::get('/attendance/list', [AttendanceController::class, 'index'])->middleware(['auth', 'verified'])->name('attendance.index');

// 一般ユーザー詳細
Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'detail'])->middleware(['auth', 'verified'])->name('attendance.detail');

Route::post('/stamp_correction_request/{attendance}', [AttendanceCorrectionController::class, 'store'])->middleware(['auth', 'verified'])->name('stamp_correction_request.store');

// 一般ユーザー申請一覧
Route::get('/stamp_correction_request/list', [AttendanceCorrectionController::class, 'index'])->middleware(['auth', 'verified'])->name('stamp_correction_request.index');

// 管理者ログイン
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');

// 管理者ログイン処理
Route::post('/admin/login', [AdminLoginController::class, 'store'])->name('admin.login.store');

// 管理者一覧
Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->middleware(['auth'])->name('admin.attendance.index');

// 管理者詳細
Route::get('/admin/attendance/{attendance}', [AdminAttendanceController::class, 'detail'])->middleware(['auth'])->name('admin.attendance.detail');

// スタッフ一覧
Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])->middleware(['auth'])->name('admin.staff.index');

// スタッフ月次勤怠一覧
Route::get('/admin/attendance/staff/{user}', [AdminAttendanceController::class, 'staff'])->middleware(['auth'])->whereNumber('user')->name('admin.attendance.staff');

// 管理者申請一覧
Route::get('/admin/stamp_correction_request/list', [AdminCorrectionController::class, 'index'])->middleware(['auth'])->name('admin.stamp_correction_request.index');

// 管理者申請承認
Route::post('/stamp_correction_request/approve/{attendance_correction}', [AdminCorrectionController::class, 'approve'])->middleware(['auth'])->whereNumber('attendance_correction')->name('admin.stamp_correction_request.approve');

// 管理者勤怠修正
Route::post('/admin/attendance/{attendance}/update', [AdminAttendanceController::class, 'update'])->middleware(['auth'])->whereNumber('attendance')->name('admin.attendance.update');

// 管理者CSV出力
Route::get('/admin/attendance/staff/{user}/csv', [AdminAttendanceController::class, 'exportCsv'])->middleware(['auth'])->whereNumber('user')->name('admin.attendance.staff.csv');

//  メール認証ルート
// 認証誘導画面（notice）
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')
    ->name('verification.notice');

// 認証メール再送
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()
        ->sendEmailVerificationNotification();
    return back();
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// メールのリンクを踏んだ時（認証完了）
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('attendance.dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');