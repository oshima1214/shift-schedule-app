# shift-schedule-app
アルバイト・パートのシフト希望をもとにシフト表を作成するWebアプリ（PHP / FuelPHP + knockout.js）

## セットアップ（Docker）

開発環境はDockerで構築する。ホスト側にPHPやMySQLを入れる必要はない。

1. `docker` ディレクトリに移動する。

   ```
   cd docker
   ```

2. イメージをビルドしてコンテナを起動する。

   ```
   docker-compose build
   docker-compose up -d
   ```

3. `fuel/app/config/development/db.php` を作成する（gitignore対象、各自ローカルで作成）。
   接続先はホストではなく `db` サービスを指す。

   ```php
   return array(
       'default' => array(
           'connection' => array(
               'dsn'      => 'mysql:host=db;port=3306;dbname=shift_schedule_app;charset=utf8mb4',
               'username' => 'root',
               'password' => 'root',
           ),
       ),
   );
   ```

4. コンテナ内でマイグレーションを実行する。デモ用の部署・従業員・シフト希望も同時に投入される。

   ```
   docker exec -w /var/www/html/my_fuel_project fuelphp-app php oil refine migrate
   ```

   テーブル定義の参照用に `db/schema.sql` を置いている（実際の作成はマイグレーション）。

5. ブラウザで http://localhost/ を開くとログイン画面が表示される。

### 構成

| サービス | 内容 | ホスト側ポート |
| --- | --- | --- |
| app | PHP 7.3 + Apache（`fuelphp-app`） | 80 |
| db | MySQL 8.0（root / root） | 3306 |

プロジェクトルートはコンテナ内の `/var/www/html/my_fuel_project` にマウントされる。
ホスト側でファイルを編集すればそのまま反映されるため、コンテナの再起動は不要。

### デモ用アカウント

| 権限 | メールアドレス | パスワード |
| --- | --- | --- |
| 管理者 | admin@example.com | Admin#12345 |
| 従業員（アルバイト） | yamada@example.com | Staff#12345 |
| 従業員（パート） | sato@example.com | Staff#12345 |
| 従業員（アルバイト） | suzuki@example.com | Staff#12345 |

シフト希望のデモデータは「今週の月曜」を起点に投入されるため、いつ実行しても初回表示で見える。

### DBの中身を確認する（HeidiSQL）

テーブルやデータの確認にはHeidiSQLを使う。dbコンテナはホストの3306番に公開しているので、ホスト側から直接繋げる。

| 項目 | 値 |
| --- | --- |
| ネットワーク種別 | MariaDB or MySQL (TCP/IP) |
| ホスト名 / IP | `127.0.0.1` |
| ポート | `3306` |
| ユーザー / パスワード | `root` / `root` |
| データベース | `shift_schedule_app` |

アプリコンテナからは `db:3306`、HeidiSQLなどホストのツールからは `127.0.0.1:3306` と、経路によって指定するホスト名が変わる点に注意する。

スキーマ変更はHeidiSQLのGUIからではなく、必ずマイグレーション（`fuel/app/migrations`）で行い、`db/schema.sql` を合わせて更新する。

## よく使うコマンド

いずれも `docker` ディレクトリで実行する。

| 目的 | コマンド |
| --- | --- |
| 起動 / 停止 | `docker-compose up -d` / `docker-compose down` |
| アクセスログ | `docker-compose logs -f app` |
| コンテナに入る | `docker exec -it fuelphp-app bash` |
| oilコマンド | `docker exec -w /var/www/html/my_fuel_project fuelphp-app php oil ...` |

FuelPHPのエラーログは `fuel/app/logs/年/月/日.php` に出る。リアルタイムで見るには次を実行する。

```
docker exec -it fuelphp-app tail -f /var/www/html/my_fuel_project/fuel/app/logs/2026/09/09.php
```

`docker-compose down` するとDBコンテナのデータも消えるため、次回起動時はマイグレーションを再実行する。
デモデータごと作り直したい場合はこれが手軽だが、データを残したい場合は `down` せず `stop` を使う。

なお `router.php` はDocker移行前にPHP組み込みサーバで動かすためのもので、現在は使っていない。

## 画面構成

| 画面 | URL | 対象 | 内容 |
| --- | --- | --- | --- |
| S01 ログイン | `/auth/login` | 共通 | メールアドレスとパスワードで認証 |
| S02 シフト希望入力 | `/shift` | 従業員 | 週単位で自分の希望を登録・編集・削除 |
| S03 シフト希望一覧 | `/request` | 管理者 | 全従業員の希望を週・部署で絞り込み表示 |
| S04 シフト表確定 | `/schedule` | 管理者 | 従業員×日付のマトリクス。セルクリックで状態切替 |
| S05 従業員管理 | `/employee` | 管理者 | 従業員の登録・編集・削除、絞り込み、ページング |

週の切り替え・絞り込み・登録・状態変更はすべてfetchによる非同期処理で、画面遷移なしに反映される。

## デモの流れ

1. **従業員でログイン**（yamada@example.com）→ シフト希望入力画面。前週/次週で切り替えつつ、希望を登録・編集・削除する。
2. **管理者でログイン**（admin@example.com）→ シフト希望一覧で提出状況を確認。
3. シフト表確定画面のセルをクリックし、「希望中 → 確定 → 却下」を切り替える。
4. 再度従業員でログインすると、確定・却下された希望は変更不可になっている（サーバサイドで検証）。
5. 従業員管理画面で従業員を追加・編集・削除する。削除は論理削除のため、過去のシフト履歴は残る。

## 状態とコード値

| 区分 | 値 | 表示 |
| --- | --- | --- |
| シフト希望の状態 | `requested` / `approved` / `rejected` | 希望中 / 確定 / 却下 |
| 雇用形態 | `part_time` / `part` | アルバイト / パート |
| 権限 | `employee` / `admin` | 従業員 / 管理者 |

表示件数や時刻の選択肢などは `fuel/app/config/shift.php` で管理している。
