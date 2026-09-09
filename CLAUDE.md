# シフト表作成アプリ

アルバイト・パートのシフト希望をもとに、管理者がシフト表を作成・確定するWebアプリ。
インターン課題として開発中。

## 技術スタック
- PHP 7.3 / FuelPHP 1.8
- MySQL 8.0
- knockout.js（フロントエンド）
- 非同期処理はfetchによるAjax
- 開発環境はDocker（app: PHP+Apache / db: MySQL）。docker/配下に定義がある

## コーディング規約
- DB操作は必ずDBクラス（クエリビルダ）を使う。生SQLの文字列連結は禁止
- Controllerのbefore()でログインチェックとCSRFトークンの準備を行う
- ビューでの出力は必ずエスケープする（knockoutのhtmlバインディングは使わない）
- 削除はdeleted_atによる論理削除。物理削除はしない
- アプリ固有の設定値はfuel/app/config/shift.phpに置き、Config::get()で参照する
- namespaceを使ってクラスを整理する

## セキュリティ要件
IPA「安全なウェブサイトの作り方」に沿って以下を実装する。
- SQLインジェクション対策：クエリビルダのプレースホルダを使う
- XSS対策：出力時のエスケープを徹底する
- CSRF対策：Security::fetch_token() / check_token() を使う
- セッション管理：ログイン成功時とパスワード変更時にセッションIDを再発行する
- 認可制御：従業員は自分のシフト希望のみ操作可能。確定済みは編集不可。
  画面上で隠すだけでなく、サーバサイドで必ず権限チェックを行う
- パスワード：password_hash()で保存する。本人による変更時は現在のパスワードを必ず照合する
- ログイン試行回数の制限：連続失敗が上限に達したアカウントを一定時間ロックする。
  回数・時間はshift.phpのloginで設定する。どちらの入力が誤りかは画面に出さない

## データベース
- departments（部署）1 - n employees（従業員）1 - n shift_requests（シフト希望）
- 接続設定はfuel/app/config/development/db.phpにあり、gitignoreされている
- アプリコンテナからの接続先はホスト名db、ポート3306
- テーブル定義はdb/schema.sqlを参照

## Git運用
- mainへの直接コミットは禁止
- developから機能ごとにfeature/xxxブランチを切る
- featureからdevelopへPRを出してマージする
- コミットメッセージは日本語で、何を実装したかを書く