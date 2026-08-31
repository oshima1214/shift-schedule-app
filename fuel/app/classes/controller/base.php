<?php
/**
 * ログインが必須な画面の共通処理をまとめた基底コントローラ。
 * CLAUDE.mdの規約: before()でログインチェックとCSRFトークンの準備を行う。
 */
class Controller_Base extends \Controller
{
	/** @var array|null  ログイン中の従業員（id, department_id, name, email, role） */
	protected $current_employee;

	/** @var string  CSRFトークン（ビューでJSに渡す） */
	protected $csrf_token;

	public function before()
	{
		parent::before();

		// CSRFトークンを準備（フォーム/Ajax双方から使えるようにビューへ渡す）
		$this->csrf_token = \Security::fetch_token();

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
}
