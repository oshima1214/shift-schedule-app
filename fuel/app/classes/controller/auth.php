<?php
/**
 * ログイン・ログアウトを扱うコントローラ。
 * ログイン前でもアクセスできる必要があるため Controller_Base は継承しない。
 */
class Controller_Auth extends \Controller
{
	public function before()
	{
		parent::before();

		// フォームのCSRFトークン埋め込み用に準備しておく
		\Security::fetch_token();
	}

	/**
	 * ログイン画面表示 / ログイン処理
	 */
	public function action_login()
	{
		// すでにログイン済みならトップへ
		if (\Session::get(\Config::get('shift.session_key')) !== null)
		{
			return \Response::redirect('shift');
		}

		$error = null;

		if (\Input::method() === 'POST')
		{
			$email    = trim((string) \Input::post('email'));
			$password = (string) \Input::post('password');

			$employee = \App\Model\Employee::authenticate($email, $password);

			if ($employee === null)
			{
				$error = 'メールアドレスまたはパスワードが正しくありません。';
			}
			else
			{
				// セッション固定攻撃対策としてログイン成功時にセッションIDを再発行する
				\Session::rotate();

				\Session::set(\Config::get('shift.session_key'), $employee['id']);

				return \Response::redirect($employee['role'] === 'admin' ? 'admin' : 'shift');
			}
		}

		$view = \View::forge('auth/login');
		$view->set('error', $error);

		return \Response::forge($view);
	}

	/**
	 * ログアウト（状態変更を伴うためPOSTのみ許可。CSRFはグローバル自動チェック対象）
	 */
	public function action_logout()
	{
		if (\Input::method() !== 'POST')
		{
			throw new \HttpNoAccessException('Method not allowed');
		}

		\Session::destroy();

		return \Response::redirect('auth/login');
	}
}
