<?php

namespace App\Model;

class ShiftRequest
{
  /**
   * 指定週の自分のシフト希望
   *
   * @param int    $employee_id
   * @param string $from  Y-m-d（月曜）
   * @param string $to    Y-m-d（日曜）
   * @return array
   */
  public static function find_own_week($employee_id, $from, $to)
  {
    return \DB::select('id', 'work_date', 'start_time', 'end_time', 'status', 'reject_reason')
      ->from('shift_requests')
      ->where('employee_id', $employee_id)
      ->where('deleted_at', null)
      ->where('work_date', 'between', array($from, $to))
      ->order_by('work_date', 'asc')
      ->execute()
      ->as_array();
  }

  /**
   * 指定週の全従業員のシフト希望（部署で絞り込み可）
   *
   * @param string   $from
   * @param string   $to
   * @param int|null $department_id
   * @return array
   */
  public static function find_week($from, $to, $department_id = null)
  {
    $query = \DB::select(
        'shift_requests.id',
        'shift_requests.employee_id',
        'shift_requests.work_date',
        'shift_requests.start_time',
        'shift_requests.end_time',
        'shift_requests.status',
        'shift_requests.reject_reason',
        array('employees.name', 'employee_name'),
        array('departments.name', 'department_name')
      )
      ->from('shift_requests')
      ->join('employees', 'inner')
      ->on('employees.id', '=', 'shift_requests.employee_id')
      ->join('departments', 'inner')
      ->on('departments.id', '=', 'employees.department_id')
      ->where('shift_requests.deleted_at', null)
      ->where('employees.deleted_at', null)
      ->where('shift_requests.work_date', 'between', array($from, $to));

    if ( ! empty($department_id))
    {
      $query->where('employees.department_id', (int) $department_id);
    }

    return $query
      ->order_by('employees.id', 'asc')
      ->order_by('shift_requests.work_date', 'asc')
      ->execute()
      ->as_array();
  }

  /**
   * @param int $id
   * @return array|null
   */
  public static function find($id)
  {
    $row = \DB::select('id', 'employee_id', 'work_date', 'start_time', 'end_time', 'status', 'reject_reason')
      ->from('shift_requests')
      ->where('id', $id)
      ->where('deleted_at', null)
      ->execute()
      ->current();

    return $row ? $row : null;
  }

  /**
   * 同じ従業員・同じ日付の希望が既にあるか。
   * シフト表は1人1日1コマで表示するため、重複を作らせない。
   *
   * @param int      $employee_id
   * @param string   $work_date
   * @param int|null $exclude_id
   * @return bool
   */
  public static function exists_on_date($employee_id, $work_date, $exclude_id = null)
  {
    $query = \DB::select('id')
      ->from('shift_requests')
      ->where('employee_id', $employee_id)
      ->where('work_date', $work_date)
      ->where('deleted_at', null);

    if ($exclude_id !== null)
    {
      $query->where('id', '!=', $exclude_id);
    }

    // 該当なしのとき current() は null を返すため、真偽値で判定する
    return (bool) $query->execute()->current();
  }

  /**
   * @param array $data
   * @return int  作成された行のID
   */
  public static function create(array $data)
  {
    list($id) = \DB::insert('shift_requests')->set(array(
      'employee_id' => $data['employee_id'],
      'work_date'   => $data['work_date'],
      'start_time'  => $data['start_time'],
      'end_time'    => $data['end_time'],
      'status'      => 'requested',
    ))->execute();

    return (int) $id;
  }

  /**
   * @param int   $id
   * @param array $data
   * @return int  更新件数
   */
  public static function update($id, array $data)
  {
    return \DB::update('shift_requests')
      ->set(array(
        'work_date'  => $data['work_date'],
        'start_time' => $data['start_time'],
        'end_time'   => $data['end_time'],
      ))
      ->where('id', $id)
      ->execute();
  }

  /**
   * 論理削除
   *
   * @param int $id
   * @return int  更新件数
   */
  public static function soft_delete($id)
  {
    return \DB::update('shift_requests')
      ->set(array('deleted_at' => \DB::expr('NOW()')))
      ->where('id', $id)
      ->execute();
  }

  /**
   * 管理者による状態変更（確定／却下／希望中に戻す）
   *
   * @param int         $id
   * @param string      $status
   * @param string|null $reject_reason  却下以外のときは無視してnullで保存する
   * @return int  更新件数
   */
  public static function set_status($id, $status, $reject_reason = null)
  {
    return static::set_status_bulk(array($id), $status, $reject_reason);
  }

  /**
   * 複数のシフト希望をまとめて状態変更する（S04の一括確定）。
   * 論理削除済みは対象外。
   *
   * @param array       $ids
   * @param string      $status
   * @param string|null $reject_reason
   * @return int  更新件数
   */
  public static function set_status_bulk(array $ids, $status, $reject_reason = null)
  {
    if (empty($ids))
    {
      return 0;
    }

    return \DB::update('shift_requests')
      ->set(array(
        'status' => $status,
        // 却下から他の状態へ戻したときに古い理由が残らないようにする
        'reject_reason' => $status === 'rejected' ? $reject_reason : null,
      ))
      ->where('id', 'in', $ids)
      ->where('deleted_at', null)
      ->execute();
  }

  /**
   * 指定したIDのうち、実在する（論理削除されていない）ものだけを返す。
   * 一括操作の前に、存在しないIDが混ざっていないか確かめるために使う。
   *
   * @param array $ids
   * @return array  IDの配列
   */
  public static function find_existing_ids(array $ids)
  {
    if (empty($ids))
    {
      return array();
    }

    $rows = \DB::select('id')
      ->from('shift_requests')
      ->where('id', 'in', $ids)
      ->where('deleted_at', null)
      ->execute()
      ->as_array();

    $found = array();
    foreach ($rows as $row)
    {
      $found[] = (int) $row['id'];
    }

    return $found;
  }

  /**
   * 日別の人員サマリを作る。
   * 確定した出勤者が0人の日をひと目で分かるようにするのが目的。
   *
   * 同じ週のデータは呼び出し側で取得済みのため、SQLのGROUP BYではなく
   * その配列をPHPで数える。問い合わせを増やさないための判断。
   *
   * @param array $days      Week::days() の7日分
   * @param array $requests  その週のシフト希望
   * @return array
   */
  public static function summarize_by_date(array $days, array $requests)
  {
    // 日付ごとに状態を数える
    $counts = array();
    foreach ($days as $day)
    {
      $counts[$day['date']] = array('approved' => 0, 'requested' => 0, 'rejected' => 0);
    }

    foreach ($requests as $request)
    {
      $date   = $request['work_date'];
      $status = $request['status'];

      if (isset($counts[$date]) and isset($counts[$date][$status]))
      {
        $counts[$date][$status]++;
      }
    }

    $summary = array();
    foreach ($days as $day)
    {
      $count = $counts[$day['date']];

      $summary[] = array(
        'date'      => $day['date'],
        'label'     => $day['label'],
        'dow'       => $day['dow'],
        'approved'  => $count['approved'],
        'requested' => $count['requested'],
        'rejected'  => $count['rejected'],
        // 確定した出勤者がいない日は警告として扱う
        'is_zero'   => $count['approved'] === 0,
      );
    }

    return $summary;
  }
}
