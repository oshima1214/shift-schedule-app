<?php
/**
 * シフト表作成アプリ ルーティング設定
 *
 *  /auth      S01 ログイン
 *  /shift     S02 シフト希望入力（従業員）
 *  /request   S03 シフト希望一覧（管理者）
 *  /schedule  S04 シフト表確定（管理者）
 *  /employee  S05 従業員管理（管理者）
 */

return array(

	'_root_' => 'auth/login',

	/**
	 * -------------------------------------------------------------------------
	 *  エラー時のルート
	 * -------------------------------------------------------------------------
	 *
	 *  ここで明示しないと public/index.php の例外ハンドリングが正しく
	 *  400/403/404/500 のビューへ振り分けられず、意図しない404になってしまう。
	 *  また、存在しないURLをコントローラに通すと、その度にCSRFトークンの
	 *  準備が走り、開いているフォームを無効化してしまう。
	 */

	'_404_' => function () { return \Response::forge(\View::forge('404'), 404); },
	'_400_' => function () { return \Response::forge(\View::forge('400'), 400); },
	'_403_' => function () { return \Response::forge(\View::forge('403'), 403); },
	'_500_' => function () { return \Response::forge(\View::forge('500'), 500); },
);
