# Kintai

## 環境構築
### Dockerビルド
1. `git clone git@github.com:KuroYUYU/test-kintai.git`
2. DockerDesktopアプリを立ち上げる
3. `docker-compose up -d --build`
### Laravel環境構築
1. `docker-compose exec php bash`
2. `composer install`
3. 「.env.example」ファイルを 「.env」ファイルに命名を変更。または、`cp.env.example.env`で新しく.envファイルを作成
4. .envに以下の環境変数を追加
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
5. アプリケーションキーの作成

`php artisan key:generate`

6. マイグレーションの実行

`php artisan migrate`

7. シーディングの実行

`php artisan db:seed`

## ログインについて
◆管理者及びスタッフユーザーでログイン画面が分かれています

シーディングにて入るログイン情報を記載します

またログイン画面のURLは一番下の部分に記載

- 管理者
  
`メールアドレス：admin@co.jp パスワード：pass12345`

- スタッフユーザー
  
`メールアドレス：staff1@example.com パスワード：pass1111`

## テスト実行方法と備考
`php artisan test ...`(テスト対象)を入れてコマンドを実行

※今回のテストケースにて疑問点があり確認した部分について記載
  
- 項目ID14「勤怠詳細情報取得・修正機能（管理者）」に正常系テストを１件追加
`Tests\Feature\Admin\AdminAttendanceDetailTest.php`

## その他今回のアプリ作成での備考
- `機能要件に記載のないバリデーションエラーメッセージ及びルールは任意で作成`
- `メール認証機能はMaiHogを使用しています,.envにてMAIL_FROM_ADDRESSは任意のアドレスを設定してください。`

## 使用技術(実行環境)
- PHP:8.1.33
- Laravel:8.83.29
- MySQL:Ver 8.0.26
- nginx:1.21.1

## テーブル設計
<img width="798" height="609" alt="スクリーンショット 2026-05-22 14 17 29" src="https://github.com/user-attachments/assets/1b91602f-3f94-4243-a042-c420ce3cf28f" />
<img width="792" height="429" alt="スクリーンショット 2026-05-22 14 20 01" src="https://github.com/user-attachments/assets/56cf3140-c151-4d15-a20a-b469ab9f3f0a" />

## ER図
<img width="721" height="572" alt="スクリーンショット 2026-05-21 20 02 49" src="https://github.com/user-attachments/assets/e881654a-f1b9-4cff-87ad-c93079291bac" />

## URL
- 開発環境：http://localhost/
- phpMyAdmin:：http://localhost:8080/
- MaiHog(メール認証用)：http://localhost:8025/
- スタッフログイン画面：http://localhost/login
- 管理者ログイン画面：http://localhost/admin/login




