<?php
/**
 * 管理者がシフト希望を確認し、シフトを確定するコントローラ。
 * 画面上で隠すだけでなく、before()でrole=adminをサーバサイドで必ず確認する。
 */
class Controller_Admin extends Controller_Base
{
	public function before()
	{
		parent::before();

		if ($this->current_employee['role'] !== 'admin')
		{
			throw new \HttpNoAccessException('管理者のみアクセスできます。');
		}
	}

	/**
	 * 管理画面（knockout.jsで一覧取得・確定を行う）
	 */
	public function action_index()
	{
		$view = \View::forge('admin/index');
		$view->set('employee_name', $this->current_employee['name']);

		return \Response::forge($view);
	}

	/**
	 * 全従業員分のシフト希望一覧をJSONで返す
	 */
	public function action_list()
	{
		$rows = \App\Model\ShiftRequest::find_all();

		return $this->json(array('data' => $rows));
	}

	/**
	 * シフト希望を確定する
	 *
	 * @param int $id
	 */
	public function action_confirm($id = null)
	{
		if (\Input::method() !== 'POST')
		{
			throw new \HttpNoAccessException('Method not allowed');
		}

		$id = (int) $id;
		$shift = $id > 0 ? \App\Model\ShiftRequest::find($id) : null;

		if ($shift === null)
		{
			return $this->json(array('errors' => array('対象のシフト希望が見つかりません。')), 404);
		}

		if ($shift['status'] === 'confirmed')
		{
			return $this->json(array('errors' => array('このシフト希望はすでに確定済みです。')), 409);
		}

		\App\Model\ShiftRequest::confirm($id);

		return $this->json(array('id' => $id));
	}
}
