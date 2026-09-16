<?php
/**
 * S02 シフト希望入力画面（従業員）
 * F02 登録 / F03 一覧表示 / F04 編集 / F05 削除 / F12 週切り替え
 *
 * どの操作も「自分自身のシフト希望のみ」に制限し、確定済みは編集不可とする。
 */
class Controller_Shift extends Controller_Base
{
  public function action_index()
  {
    $view = \View::forge('shift/index');
    $view->set('employee', $this->current_employee);
    $view->set('menu', $this->nav_menu());
    $view->set('employment_label', \Arr::get(\Config::get('shift.employment_type'), $this->current_employee['employment_type'], ''));
    $view->set('statuses', \Config::get('shift.status'));
    $view->set('time_options', static::time_options());

    return \Response::forge($view);
  }

  /**
   * 指定週の自分のシフト希望を、7日分の枠に埋めて返す
   */
  public function action_list()
  {
    $monday = \App\Support\Week::monday(\Input::get('week'));
    $days   = \App\Support\Week::days($monday);

    $requests = \App\Model\ShiftRequest::find_own_week(
      $this->current_employee['id'],
      $monday->format('Y-m-d'),
      \App\Support\Week::sunday($monday)
    );

    // 日付をキーにして引けるようにする
    $by_date = array();
    foreach ($requests as $request)
    {
      $by_date[$request['work_date']] = $request;
    }

    $rows = array();
    foreach ($days as $day)
    {
      $request = \Arr::get($by_date, $day['date']);

      $rows[] = array(
        'date'       => $day['date'],
        'label'      => $day['label'],
        'date_label' => date('m/d', strtotime($day['date'])),
        'dow'        => $day['dow'],
        'id'         => $request ? (int) $request['id'] : null,
        'start_time' => $request ? substr($request['start_time'], 0, 5) : null,
        'end_time'   => $request ? substr($request['end_time'], 0, 5) : null,
        'status'     => $request ? $request['status'] : null,
        // 却下された理由を本人にも見せる
        'reject_reason' => ($request and $request['status'] === 'rejected') ? $request['reject_reason'] : null,
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

  public function action_create()
  {
    $this->require_post();

    list($data, $errors) = $this->validate_input();

    if ( ! empty($errors))
    {
      return $this->json_errors($errors);
    }

    if (\App\Model\ShiftRequest::exists_on_date($this->current_employee['id'], $data['work_date']))
    {
      return $this->json_errors(array('この日のシフト希望はすでに登録されています。'));
    }

    $data['employee_id'] = $this->current_employee['id'];

    return $this->json(array('id' => \App\Model\ShiftRequest::create($data)), 201);
  }

  public function action_update($id = null)
  {
    $this->require_post();

    $shift = $this->find_own_editable_shift((int) $id);

    if ($shift === null)
    {
      return $this->json(array('errors' => array('対象のシフト希望が見つからないか、編集できません。')), 404);
    }

    list($data, $errors) = $this->validate_input();

    if ( ! empty($errors))
    {
      return $this->json_errors($errors);
    }

    if (\App\Model\ShiftRequest::exists_on_date($this->current_employee['id'], $data['work_date'], $shift['id']))
    {
      return $this->json_errors(array('この日のシフト希望はすでに登録されています。'));
    }

    \App\Model\ShiftRequest::update($shift['id'], $data);

    return $this->json(array('id' => $shift['id']));
  }

  public function action_delete($id = null)
  {
    $this->require_post();

    $shift = $this->find_own_editable_shift((int) $id);

    if ($shift === null)
    {
      return $this->json(array('errors' => array('対象のシフト希望が見つからないか、削除できません。')), 404);
    }

    \App\Model\ShiftRequest::soft_delete($shift['id']);

    return $this->json(array('id' => $shift['id']));
  }

  /**
   * 自分が所有し、かつ希望中（編集可能）なシフト希望を取得する。
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

    // 確定・却下済みは本人でも変更できない
    if ($shift['status'] !== 'requested')
    {
      return null;
    }

    return $shift;
  }

  /**
   * 入力値の検証（サーバサイドで必ず行う）
   *
   * @return array [array $data, array $errors]
   */
  private function validate_input()
  {
    $errors = array();

    $work_date  = (string) \Input::json('work_date', '');
    $start_time = (string) \Input::json('start_time', '');
    $end_time   = (string) \Input::json('end_time', '');

    // 表示中の週をそのまま編集できるよう、今週の月曜を下限にする
    $min_date = new \DateTime('monday this week');
    $max_date = (clone $min_date)->modify('+'.(int) \Config::get('shift.request_range_days').' days');

    $work_date_obj = \DateTime::createFromFormat('Y-m-d', $work_date);

    if ( ! $work_date_obj or $work_date_obj->format('Y-m-d') !== $work_date)
    {
      $errors[] = '勤務日の形式が正しくありません。';
    }
    else
    {
      $work_date_obj->setTime(0, 0, 0);

      if ($work_date_obj < $min_date or $work_date_obj > $max_date)
      {
        $errors[] = '勤務日は今週から'.(int) \Config::get('shift.request_range_days').'日以内で指定してください。';
      }
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

    return array(
      array('work_date' => $work_date, 'start_time' => $start_time, 'end_time' => $end_time),
      $errors,
    );
  }

  /**
   * 時刻セレクトの選択肢をconfigから組み立てる
   *
   * @return array
   */
  private static function time_options()
  {
    $config = \Config::get('shift.time_options');

    $current = \DateTime::createFromFormat('H:i', $config['start']);
    $last    = \DateTime::createFromFormat('H:i', $config['end']);

    $options = array();

    while ($current <= $last)
    {
      $options[] = $current->format('H:i');
      $current->modify('+'.(int) $config['step'].' minutes');
    }

    return $options;
  }
}
