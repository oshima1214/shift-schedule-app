# shift-schedule-app
アルバイト・パートのシフト希望をもとにシフト表を作成するWebアプリ（PHP / FuelPHP + knockout.js）

## セットアップ

1. `fuel/app/config/development/db.php` にMySQLの接続情報を設定する（gitignore対象、各自ローカルで作成）。
2. `db/schema.sql` の内容、または以下のマイグレーションでテーブルを作成する。

   ```
   php oil refine migrate
   ```

   `002_create_employees.php` でデモ用アカウントを自動投入します。
   - 管理者: admin@example.com / Admin#12345
   - スタッフ: staff@example.com / Staff#12345

## ローカル起動（PHP組み込みサーバ）

Apacheの`.htaccess`相当の書き換えを行う `router.php` を経由して起動する。

```
php -S 127.0.0.1:8080 router.php
```

ブラウザで http://127.0.0.1:8080/ を開くとログイン画面が表示される。

## デモの流れ

1. スタッフでログイン → シフト希望（勤務日・時間・備考）を登録／編集／削除する。
2. 管理者でログイン → 全従業員分のシフト希望一覧を確認し、「確定する」でシフトを確定する。
3. 確定済みのシフト希望は、スタッフ側では編集・削除できなくなる（サーバサイドでチェック）。
