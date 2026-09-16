<?php
/**
 * S04 シフト表確定画面（管理者）
 * F09 シフト割り当て確定 / F10 シフト表表示（マトリクス） / F11 絞り込み / F12 週切り替え
 * F15 日別人員サマリ / F16 却下理由の記録 / F17 一括確定
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
    $view->set('menu', $this->nav_menu());
    $view->set('departments', \App\Model\Department::find_all());
    $view->set('statuses', \Config::get('shift.status'));
    $view->set('reason_max_length', (int) \Config::get('shift.reject_reason_max_length'));

    return \Response::forge($view);
  }

  /**
   * 従業員×曜日のマトリクスと、日別の人員サマリを返す
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
        'id'            => (int) $request['id'],
        'time'          => substr($request['start_time'], 0, 2).'-'.substr($request['end_time'], 0, 2),
        'status'        => $request['status'],
        'reject_reason' => $request['reject_reason'],
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
      'summary'   => \App\Model\ShiftRequest::summarize_by_date($days, $requests),
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

    list($status, $reason, $errors) = $this->validate_status_input();

    if ( ! empty($errors))
    {
      return $this->json_errors($errors);
    }

    \App\Model\ShiftRequest::set_status($id, $status, $reason);

    $statuses = \Config::get('shift.status');

    return $this->json(array(
      'id'            => $id,
      'status'        => $status,
      'status_label'  => $statuses[$status],
      'reject_reason' => $status === 'rejected' ? $reason : null,
    ));
  }

  /**
   * 複数のシフト希望をまとめて状態変更する（一括確定）。
   * 画面に表示中の希望中セルのIDをまとめて受け取る。
   */
  public function action_bulk_status()
  {
    $this->require_post();

    list($status, $reason, $errors) = $this->validate_status_input();

    $ids = $this->validate_ids(\Input::json('ids', array()), $errors);

    if ( ! empty($errors))
    {
      return $this->json_errors($errors);
    }

    // 削除済みや存在しないIDが混ざっていても、実在するものだけを処理する
    $targets = \App\Model\ShiftRequest::find_existing_ids($ids);

    if (empty($targets))
    {
      return $this->json(array('errors' => array('対象のシフト希望が見つかりません。')), 404);
    }

    $updated = \App\Model\ShiftRequest::set_status_bulk($targets, $status, $reason);

    return $this->json(array(
      'status'       => $status,
      'status_label' => \Arr::get(\Config::get('shift.status'), $status, $status),
      'updated'      => (int) $updated,
      'requested'    => count($ids),
    ));
  }

  /**
   * 状態と却下理由の検証（単体・一括で共通）
   *
   * @return array [string $status, string|null $reason, array $errors]
   */
  private function validate_status_input()
  {
    $errors = array();

    $status   = (string) \Input::json('status', '');
    $statuses = \Config::get('shift.status');

    // 設定にある状態以外は受け付けない
    if ( ! array_key_exists($status, $statuses))
    {
      $errors[] = '指定された状態は無効です。';
    }

    $reason     = null;
    $max_length = (int) \Config::get('shift.reject_reason_max_length');

    // 却下理由は任意。入力があったときだけ保存し、ほかの状態では保存しない。
    if ($status === 'rejected')
    {
      $reason = trim((string) \Input::json('reject_reason', ''));

      if (mb_strlen($reason) > $max_length)
      {
        $errors[] = '却下理由は'.$max_length.'文字以内で入力してください。';
      }

      // 空文字ではなくnullで持たせ、「理由なし」を一通りに揃える
      if ($reason === '')
      {
        $reason = null;
      }
    }

    return array($status, $reason, $errors);
  }

  /**
   * 一括操作の対象IDを検証する
   *
   * @param mixed $input
   * @param array $errors  エラーがあれば追記する
   * @return array  正の整数のID（重複なし）
   */
  private function validate_ids($input, array &$errors)
  {
    if ( ! is_array($input) or empty($input))
    {
      $errors[] = '対象のシフト希望が選択されていません。';

      return array();
    }

    $max = (int) \Config::get('shift.bulk_max_count');

    if (count($input) > $max)
    {
      $errors[] = '一度に処理できるのは'.$max.'件までです。';

      return array();
    }

    $ids = array();
    foreach ($input as $value)
    {
      $id = (int) $value;

      if ($id > 0)
      {
        $ids[$id] = $id;
      }
    }

    if (empty($ids))
    {
      $errors[] = '対象のシフト希望が選択されていません。';
    }

    return array_values($ids);
  }
}
