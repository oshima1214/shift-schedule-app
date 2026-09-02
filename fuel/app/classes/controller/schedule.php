<?php
/**
 * S04 シフト表確定画面（管理者）
 * F09 シフト割り当て確定 / F10 シフト表表示（マトリクス） / F11 絞り込み / F12 週切り替え
 */
class Controller_Schedule extends Controller_Base
{
	public function before()
	{
		parent::before();

		$this->require_admin();
	}

	public function action_index()
	{
		$view = \View::forge('schedule/index');
		$view->set('employee', $this->current_employee);
		$view->set('departments', \App\Model\Department::find_all());
		$view->set('statuses', \Config::get('shift.status'));

		return \Response::forge($view);
	}

	/**
	 * 従業員×曜日のマトリクス形式で返す
	 */
	public function action_list()
	{
		$monday        = \App\Support\Week::monday(\Input::get('week'));
		$days          = \App\Support\Week::days($monday);
		$department_id = \Input::get('department_id');

		$requests = \App\Model\ShiftRequest::find_week(
			$monday->format('Y-m-d'),
			\App\Support\Week::sunday($monday),
			$department_id
		);

		// 従業員ごとに、日付をキーにしたセルへ詰め替える
		$employees = array();
		foreach ($requests as $request)
		{
			$employee_id = (int) $request['employee_id'];

			if ( ! isset($employees[$employee_id]))
			{
				$employees[$employee_id] = array(
					'employee_id'     => $employee_id,
					'employee_name'   => $request['employee_name'],
					'department_name' => $request['department_name'],
					'cells'           => array(),
				);
			}

			$employees[$employee_id]['cells'][$request['work_date']] = array(
				'id'     => (int) $request['id'],
				'time'   => substr($request['start_time'], 0, 2).'-'.substr($request['end_time'], 0, 2),
				'status' => $request['status'],
			);
		}

		// 7日分の枠を必ず埋める（希望なしの日はnull）
		$rows = array();
		foreach ($employees as $employee)
		{
			$cells = array();

			foreach ($days as $day)
			{
				$cells[] = \Arr::get($employee['cells'], $day['date']);
			}

			$employee['cells'] = $cells;
			$rows[] = $employee;
		}

		return $this->json(array(
			'week'      => $monday->format('Y-m-d'),
			'label'     => \App\Support\Week::label($monday),
			'prev_week' => (clone $monday)->modify('-7 days')->format('Y-m-d'),
			'next_week' => (clone $monday)->modify('+7 days')->format('Y-m-d'),
			'days'      => $days,
			'rows'      => $rows,
		));
	}

	/**
	 * シフト希望の状態を変更する（希望中 → 確定 → 却下 の切り替え）
	 *
	 * @param int $id
	 */
	public function action_status($id = null)
	{
		$this->require_post();

		$id    = (int) $id;
		$shift = $id > 0 ? \App\Model\ShiftRequest::find($id) : null;

		if ($shift === null)
		{
			return $this->json(array('errors' => array('対象のシフト希望が見つかりません。')), 404);
		}

		$status   = (string) \Input::json('status', '');
		$statuses = \Config::get('shift.status');

		// 設定にある状態以外は受け付けない
		if ( ! array_key_exists($status, $statuses))
		{
			return $this->json_errors(array('指定された状態は無効です。'));
		}

		\App\Model\ShiftRequest::set_status($id, $status);

		return $this->json(array('id' => $id, 'status' => $status, 'status_label' => $statuses[$status]));
	}
}
