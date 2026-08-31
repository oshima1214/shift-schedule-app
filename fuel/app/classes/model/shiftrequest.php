<?php

namespace App\Model;

class ShiftRequest
{
	/**
	 * 自分のシフト希望一覧（論理削除済みは除く）
	 *
	 * @param int $employee_id
	 * @return array
	 */
	public static function find_by_employee(int $employee_id)
	{
		return \DB::select('id', 'work_date', 'start_time', 'end_time', 'status', 'note')
			->from('shift_requests')
			->where('employee_id', $employee_id)
			->where('deleted_at', null)
			->order_by('work_date', 'asc')
			->order_by('start_time', 'asc')
			->execute()
			->as_array();
	}

	/**
	 * 管理者向け：全従業員分のシフト希望一覧（論理削除済みは除く）
	 *
	 * @return array
	 */
	public static function find_all()
	{
		return \DB::select(
				'shift_requests.id',
				'shift_requests.employee_id',
				'shift_requests.work_date',
				'shift_requests.start_time',
				'shift_requests.end_time',
				'shift_requests.status',
				'shift_requests.note',
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
			->order_by('shift_requests.work_date', 'asc')
			->order_by('shift_requests.start_time', 'asc')
			->execute()
			->as_array();
	}

	/**
	 * 1件取得（存在しない/削除済みなら null）
	 *
	 * @param int $id
	 * @return array|null
	 */
	public static function find(int $id)
	{
		$row = \DB::select('id', 'employee_id', 'work_date', 'start_time', 'end_time', 'status', 'note')
			->from('shift_requests')
			->where('id', $id)
			->where('deleted_at', null)
			->execute()
			->current();

		return $row === false ? null : $row;
	}

	/**
	 * @param array $data  employee_id, work_date, start_time, end_time, note
	 * @return int  作成された行のID
	 */
	public static function create(array $data)
	{
		$now = \DB::expr('NOW()');

		list($id) = \DB::insert('shift_requests')->set(array(
			'employee_id' => $data['employee_id'],
			'work_date'   => $data['work_date'],
			'start_time'  => $data['start_time'],
			'end_time'    => $data['end_time'],
			'status'      => 'pending',
			'note'        => $data['note'],
			'created_at'  => $now,
			'updated_at'  => $now,
		))->execute();

		return $id;
	}

	/**
	 * @param int   $id
	 * @param array $data  work_date, start_time, end_time, note
	 * @return int  更新件数
	 */
	public static function update(int $id, array $data)
	{
		return \DB::update('shift_requests')
			->set(array(
				'work_date'  => $data['work_date'],
				'start_time' => $data['start_time'],
				'end_time'   => $data['end_time'],
				'note'       => $data['note'],
				'updated_at' => \DB::expr('NOW()'),
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
	public static function soft_delete(int $id)
	{
		return \DB::update('shift_requests')
			->set(array('deleted_at' => \DB::expr('NOW()')))
			->where('id', $id)
			->execute();
	}

	/**
	 * 管理者によるシフト確定
	 *
	 * @param int $id
	 * @return int  更新件数
	 */
	public static function confirm(int $id)
	{
		return \DB::update('shift_requests')
			->set(array(
				'status'     => 'confirmed',
				'updated_at' => \DB::expr('NOW()'),
			))
			->where('id', $id)
			->execute();
	}
}
