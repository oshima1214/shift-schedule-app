<?php
/**
 * S01 ログイン画面 / F01 ログイン・ログアウト
 * ログイン前でもアクセスできる必要があるため Controller_Base は継承しない。
 */
class Controller_Auth extends \Controller
{
	public function before()
	{
		parent::before();

		// フォームのCSRFトークン準備と検証（詳細は App\Support\Csrf を参照）
		\App\Support\Csrf::prepare();
	}

	/**
	 * ログイン画面をブラウザにキャッシュさせない。
	 * キャッシュされた古いフォームを再送すると、CSRF検証で弾かれてしまう。
	 *
	 * @param \Response|string $response
	 * @return \Response
	 */
	public function after($response)
	{
		$response = parent::after($response);

		$response->set_header('Cache-Control', 'no-store, no-cache, must-revalidate');
		$response->set_header('Pragma', 'no-cache');

		return $response;
	}

	/**
	 * ログイン画面表示 / ログイン処理
	 */
	public function action_login()
	{
		if (\Session::get(\Config::get('shift.session_key')) !== null)
		{
			return \Response::redirect(static::home_for(\Session::get('role')));
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
				// セッション固定攻撃対策としてログイン成功時にセッションIDを再発行する。
				// 権限が変わるタイミングなのでCSRFトークンもここで作り直す。
				\Session::rotate();
				\Security::set_token(true);

				\Session::set(\Config::get('shift.session_key'), $employee['id']);
				\Session::set('role', $employee['role']);

				return \Response::redirect(static::home_for($employee['role']));
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

	/**
	 * 権限ごとの最初の画面
	 *
	 * @param string|null $role
	 * @return string
	 */
	private static function home_for($role)
	{
		return $role === 'admin' ? 'request' : 'shift';
	}
}
