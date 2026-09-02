<?php
/**
 * S03 シフト希望一覧画面（管理者）
 * F03 一覧表示 / F11 絞り込み / F12 週切り替え
 */
class Controller_Request extends Controller_Base
{
	public function before()
	{
		parent::before();

		$this->require_admin();
	}

	public function action_index()
	{
		$view = \View::forge('request/index');
		$view->set('employee', $this->current_employee);
		$view->set('departments', \App\Model\Department::find_all());

		return \Response::forge($view);
	}

	/**
	 * 指定週・指定部署の全従業員のシフト希望を一覧で返す
	 */
	public function action_list()
	{
		$monday        = \App\Support\Week::monday(\Input::get('week'));
		$department_id = \Input::get('department_id');

		$requests = \App\Model\ShiftRequest::find_week(
			$monday->format('Y-m-d'),
			\App\Support\Week::sunday($monday),
			$department_id
		);

		$statuses = \Config::get('shift.status');

		$rows = array();
		foreach ($requests as $request)
		{
			$rows[] = array(
				'id'              => (int) $request['id'],
				'employee_name'   => $request['employee_name'],
				'department_name' => $request['department_name'],
				'work_date'       => $request['work_date'],
				'date_label'      => date('m/d', strtotime($request['work_date'])),
				'start_time'      => substr($request['start_time'], 0, 5),
				'end_time'        => substr($request['end_time'], 0, 5),
				'status'          => $request['status'],
				'status_label'    => \Arr::get($statuses, $request['status'], $request['status']),
			);
		}

		return $this->json(array(
			'week'      => $monday->format('Y-m-d'),
			'label'     => \App\Support\Week::label($monday),
			'prev_week' => (clone $monday)->modify('-7 days')->format('Y-m-d'),
			'next_week' => (clone $monday)->modify('+7 days')->format('Y-m-d'),
			'rows'      => $rows,
		));
	}
}
