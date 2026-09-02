<?php
/**
 * ログインが必須な画面の共通処理をまとめた基底コントローラ。
 * CLAUDE.mdの規約: before()でログインチェックとCSRFトークンの準備を行う。
 */
class Controller_Base extends \Controller
{
	/** @var array|null  ログイン中の従業員 */
	protected $current_employee;

	public function before()
	{
		parent::before();

		// CSRFトークンを準備する（詳細は App\Support\Csrf を参照）
		\App\Support\Csrf::prepare();

		// ログインチェック（未ログインならログイン画面へリダイレクトして終了）
		$employee_id = \Session::get(\Config::get('shift.session_key'));

		if ($employee_id === null)
		{
			\Response::redirect('auth/login')->send(true);
			exit;
		}

		$this->current_employee = \App\Model\Employee::find((int) $employee_id);

		if ($this->current_employee === null)
		{
			// 削除済み従業員などセッションが無効なケース
			\Session::destroy();
			\Response::redirect('auth/login')->send(true);
			exit;
		}
	}

	/**
	 * ログイン後の画面はブラウザにキャッシュさせない。
	 * 戻るボタンで他人に内容が見えたり、古いCSRFトークンを含む
	 * フォームが再送されたりするのを防ぐ。
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
	 * 管理者専用画面で呼ぶ。画面で隠すだけでなくサーバ側で必ず確認する。
	 *
	 * @throws HttpNoAccessException
	 */
	protected function require_admin()
	{
		if ($this->current_employee['role'] !== 'admin')
		{
			throw new \HttpNoAccessException('管理者のみアクセスできます。');
		}
	}

	/**
	 * POST以外を弾く（更新系アクションの入口で呼ぶ）
	 *
	 * @throws HttpNoAccessException
	 */
	protected function require_post()
	{
		if (\Input::method() !== 'POST')
		{
			throw new \HttpNoAccessException('Method not allowed');
		}
	}

	/**
	 * JSON APIレスポンスを生成する
	 *
	 * @param mixed $data
	 * @param int   $status
	 * @return \Response
	 */
	protected function json($data, $status = 200)
	{
		return \Response::forge(
			json_encode($data, JSON_UNESCAPED_UNICODE),
			$status,
			array('Content-Type' => 'application/json; charset=utf-8')
		);
	}

	/**
	 * バリデーションエラーをまとめて返す
	 *
	 * @param array $errors
	 * @return \Response
	 */
	protected function json_errors(array $errors)
	{
		return $this->json(array('errors' => $errors), 422);
	}
}
