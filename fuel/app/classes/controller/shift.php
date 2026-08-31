<?php
/**
 * 従業員が自分自身のシフト希望を登録・編集・削除するコントローラ。
 * どの操作も「自分自身のシフト希望のみ」に制限し、確定済みは編集不可とする。
 */
class Controller_Shift extends Controller_Base
{
	/**
	 * シフト希望画面（knockout.jsでlist/create/update/deleteを呼び出す）
	 */
	public function action_index()
	{
		$view = \View::forge('shift/index');
		$view->set('employee_name', $this->current_employee['name']);
		$view->set('request_range_days', \Config::get('shift.request_range_days'));

		return \Response::forge($view);
	}

	/**
	 * 自分のシフト希望一覧をJSONで返す
	 */
	public function action_list()
	{
		$rows = \App\Model\ShiftRequest::find_by_employee($this->current_employee['id']);

		return $this->json(array('data' => $rows));
	}

	/**
	 * シフト希望を新規登録する
	 */
	public function action_create()
	{
		if (\Input::method() !== 'POST')
		{
			throw new \HttpNoAccessException('Method not allowed');
		}

		list($data, $errors) = $this->validate_input();

		if ( ! empty($errors))
		{
			return $this->json(array('errors' => $errors), 422);
		}

		$data['employee_id'] = $this->current_employee['id'];
		$id = \App\Model\ShiftRequest::create($data);

		return $this->json(array('id' => $id), 201);
	}

	/**
	 * シフト希望を更新する（自分のもの、かつ未確定のみ）
	 *
	 * @param int $id
	 */
	public function action_update($id = null)
	{
		if (\Input::method() !== 'POST')
		{
			throw new \HttpNoAccessException('Method not allowed');
		}

		$shift = $this->find_own_editable_shift((int) $id);

		if ($shift === null)
		{
			return $this->json(array('errors' => array('対象のシフト希望が見つからないか、編集できません。')), 404);
		}

		list($data, $errors) = $this->validate_input();

		if ( ! empty($errors))
		{
			return $this->json(array('errors' => $errors), 422);
		}

		\App\Model\ShiftRequest::update($shift['id'], $data);

		return $this->json(array('id' => $shift['id']));
	}

	/**
	 * シフト希望を削除する（論理削除。自分のもの、かつ未確定のみ）
	 *
	 * @param int $id
	 */
	public function action_delete($id = null)
	{
		if (\Input::method() !== 'POST')
		{
			throw new \HttpNoAccessException('Method not allowed');
		}

		$shift = $this->find_own_editable_shift((int) $id);

		if ($shift === null)
		{
			return $this->json(array('errors' => array('対象のシフト希望が見つからないか、削除できません。')), 404);
		}

		\App\Model\ShiftRequest::soft_delete($shift['id']);

		return $this->json(array('id' => $shift['id']));
	}

	/**
	 * 自分自身が所有し、かつ未確定（編集可能）なシフト希望を取得する。
	 * ID指定だけで他人のデータを操作できないよう、必ず employee_id も照合する。
	 *
	 * @param int $id
	 * @return array|null
	 */
	private function find_own_editable_shift($id)
	{
		if ($id <= 0)
		{
			return null;
		}

		$shift = \App\Model\ShiftRequest::find($id);

		if ($shift === null)
		{
			return null;
		}

		if ((int) $shift['employee_id'] !== (int) $this->current_employee['id'])
		{
			return null;
		}

		if ($shift['status'] === 'confirmed')
		{
			return null;
		}

		return $shift;
	}

	/**
	 * 入力値のバリデーション（サーバサイドで必ず検証する）
	 *
	 * @return array [array $data, array $errors]
	 */
	private function validate_input()
	{
		$errors = array();

		// このアプリはJSONボディでリクエストを送るため \Input::json() で読む
		$work_date  = (string) \Input::json('work_date', '');
		$start_time = (string) \Input::json('start_time', '');
		$end_time   = (string) \Input::json('end_time', '');
		$note       = trim((string) \Input::json('note', ''));

		$today    = new \DateTime('today');
		$max_date = (clone $today)->modify('+'.(int) \Config::get('shift.request_range_days').' days');

		$work_date_obj = \DateTime::createFromFormat('Y-m-d', $work_date);

		if ( ! $work_date_obj or $work_date_obj->format('Y-m-d') !== $work_date)
		{
			$errors[] = '勤務日の形式が正しくありません。';
		}
		elseif ($work_date_obj < $today or $work_date_obj > $max_date)
		{
			$errors[] = '勤務日は本日から'.(int) \Config::get('shift.request_range_days').'日以内で指定してください。';
		}

		if ( ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start_time))
		{
			$errors[] = '開始時刻の形式が正しくありません。';
		}

		if ( ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end_time))
		{
			$errors[] = '終了時刻の形式が正しくありません。';
		}

		if (empty($errors) and $start_time >= $end_time)
		{
			$errors[] = '終了時刻は開始時刻より後にしてください。';
		}

		if (mb_strlen($note) > 255)
		{
			$errors[] = '備考は255文字以内で入力してください。';
		}

		$data = array(
			'work_date'  => $work_date,
			'start_time' => $start_time,
			'end_time'   => $end_time,
			'note'       => $note === '' ? null : $note,
		);

		return array($data, $errors);
	}
}
