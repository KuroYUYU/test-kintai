<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    // 2 : ログイン認証機能（一般ユーザー）

    // Case1 メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_staff_email_is_required()
    {
        $response = $this->post('/login', [
            'email' => '', // メールアドレス未入力
            'password' => 'password123',
        ]);

        // バリデーションで戻る
        $response->assertStatus(302);

        $response->assertSessionHasErrors(['email']);

        // メッセージが要件文言どおりであること
        $this->assertSame(
            'メールアドレスを入力してください',
            session('errors')->first('email')
        );
    }

    // Case2 パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_staff_password_is_required()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '', // パスワード未入力
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['password']);

        $this->assertSame(
            'パスワードを入力してください',
            session('errors')->first('password')
        );
    }

    // Case3 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_staff_login_failed_with_wrong_password()
    {
        // 事前にユーザー作成し正しいパスワードで保存
        User::create([
            'name' => 'テスト　太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_STAFF,
        ]);

        // 間違ったパスワードでログイン
        $response = $this->post('/login', [
            'email' => 'test99@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(302);

        // emailの下部にエラー表示
        $response->assertSessionHasErrors(['email']);

        $this->assertSame(
            'ログイン情報が登録されていません',
            session('errors')->first('email')
        );
    }

    // 3 : ログイン認証機能（管理者ユーザー）

    // Case1 メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_admin_email_is_required()
    {
        $response = $this->post('/admin/login', [
            'email' => '', // メールアドレス未入力
            'password' => 'password123',
        ]);

        // バリデーションで戻る
        $response->assertStatus(302);

        $response->assertSessionHasErrors(['email']);

        // メッセージが要件文言どおりであること
        $this->assertSame(
            'メールアドレスを入力してください',
            session('errors')->first('email')
        );
    }

    // Case2 パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_admin_password_is_required()
    {
        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => '', // パスワード未入力
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['password']);

        $this->assertSame(
            'パスワードを入力してください',
            session('errors')->first('password')
        );
    }

    // Case3 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_admin_login_failed_with_wrong_password()
    {
        // 事前にユーザー作成し正しいパスワードで保存
        User::create([
            'name' => 'テスト　太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        // 間違ったパスワードでログイン
        $response = $this->post('/admin/login', [
            'email' => 'test99@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(302);

        // emailの下部にエラー表示
        $response->assertSessionHasErrors(['email']);

        $this->assertSame(
            'ログイン情報が登録されていません',
            session('errors')->first('email')
        );
    }
}
