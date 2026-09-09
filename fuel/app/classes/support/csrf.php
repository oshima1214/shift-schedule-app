<?php

namespace App\Support;

/**
 * CSRFトークンの準備と検証。
 *
 * FuelPHPの自動チェック（security.csrf_autoload）は Security::_init() の中で
 * check_token() を呼ぶが、その内部で fetch_token() が評価されるため、
 * トークンがまだ確定していない時点で「新しいトークンを発行 ＋ Cookie更新」が
 * 走ってしまう。結果としてPOSTの度にCookieの値が変わり、
 *
 *   ・ブラウザがキャッシュしたフォーム
 *   ・別タブで開いたままのフォーム
 *   ・戻るボタンで表示したフォーム
 *
 * が古いトークンを送ることになり、正規の操作が400で弾かれる。
 *
 * そこで自動チェックは無効にし、
 *   1. 先に set_token(false) で「Cookieにある既存トークン」を確定させる
 *   2. その上で check_token() を呼ぶ
 * という順序にしている。こうすると check_token() 内の fetch_token() は
 * 確定済みの値を返すだけになり、新規発行が起きない。
 * （security.csrf_rotate も false にしてあるため検証後の再発行もしない）
 *
 * トークンはセッション単位で固定となる。これは一般的なCSRF対策の方式で、
 * ログイン成功時など権限が変わるタイミングでは明示的に作り直している。
 */
class Csrf
{
	/**
	 * トークンを準備し、更新系リクエストなら検証する。
	 *
	 * @throws \HttpBadRequestException  検証に失敗した場合
	 */
	public static function prepare()
	{
		// 1. Cookieに既存トークンがあれば使い回す（無いときだけ新規発行してCookieに載せる）
		\Security::set_token(false);

		$checked_methods = \Config::get('security.csrf_autoload_methods', array('post', 'put', 'delete'));

		if ( ! in_array(strtolower(\Input::method()), $checked_methods, true))
		{
			return;
		}

		// 2. 更新系リクエストはここで検証する
		if ( ! \Security::check_token())
		{
			throw new \HttpBadRequestException('CSRFトークンの検証に失敗しました。');
		}
	}
}
