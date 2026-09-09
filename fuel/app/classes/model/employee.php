<?php

namespace App\Model;

class Employee
{
	/**
	 * email + 平文パスワードで認証し、成功したら従業員情報を返す
	 *
	 * @param string $email
	 * @param string $password
	 * @return array|null
	 */
	public static function authenticate($email, $password)
	{
		$row = \DB::select('id', 'department_id', 'name', 'email', 'password_hash', 'employment_type', 'role')
			->from('employees')
			->where('email', $email)
			->where('deleted_at', null)
			->execute()
			->current();

		// 該当なしのとき current() は null を返すため、真偽値で判定する
		if ( ! $row or ! password_verify($password, $row['password_hash']))
		{
			return null;
		}

		unset($row['password_hash']);

		return $row;
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	public static function find($id)
	{
		$row = \DB::select(
				'employees.id',
				'employees.department_id',
				'employees.name',
				'employees.email',
				'employees.employment_type',
				'employees.role',
				array('departments.name', 'department_name')
			)
			->from('employees')
			->join('departments', 'inner')
			->on('departments.id', '=', 'employees.department_id')
			->where('employees.id', $id)
			->where('employees.deleted_at', null)
			->execute()
			->current();

		return $row ? $row : null;
	}

	/**
	 * 絞り込み・ページング付きの従業員一覧
	 *
	 * @param array $filters  department_id / employment_type
	 * @param int   $page
	 * @param int   $per_page
	 * @return array
	 */
	public static function find_paged(array $filters, $page, $per_page)
	{
		$query = \DB::select(
				'employees.id',
				'employees.department_id',
				'employees.name',
				'employees.email',
				'employees.employment_type',
				'employees.role',
				array('departments.name', 'department_name')
			)
			->from('employees')
			->join('departments', 'inner')
			->on('departments.id', '=', 'employees.department_id')
			->where('employees.deleted_at', null);

		static::apply_filters($query, $filters);

		return $query
			->order_by('employees.id', 'asc')
			->limit($per_page)
			->offset(($page - 1) * $per_page)
			->execute()
			->as_array();
	}

	/**
	 * 絞り込み条件に一致する件数
	 *
	 * @param array $filters
	 * @return int
	 */
	public static function count_filtered(array $filters)
	{
		$query = \DB::select(\DB::expr('COUNT(*) AS total'))
			->from('employees')
			->where('employees.deleted_at', null);

		static::apply_filters($query, $filters);

		$row = $query->execute()->current();

		return (int) $row['total'];
	}

	/**
	 * @param array $data
	 * @return int  作成された行のID
	 */
	public static function create(array $data)
	{
		list($id) = \DB::insert('employees')->set(array(
			'department_id'   => $data['department_id'],
			'name'            => $data['name'],
			'email'           => $data['email'],
			'password_hash'   => password_hash($data['password'], PASSWORD_DEFAULT),
			'employment_type' => $data['employment_type'],
			'role'            => $data['role'],
		))->execute();

		return (int) $id;
	}

	/**
	 * パスワードは指定があるときだけ更新する
	 *
	 * @param int   $id
	 * @param array $data
	 * @return int  更新件数
	 */
	public static function update($id, array $data)
	{
		$values = array(
			'department_id'   => $data['department_id'],
			'name'            => $data['name'],
			'email'           => $data['email'],
			'employment_type' => $data['employment_type'],
			'role'            => $data['role'],
		);

		if ( ! empty($data['password']))
		{
			$values['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
		}

		return \DB::update('employees')->set($values)->where('id', $id)->execute();
	}

	/**
	 * 論理削除。過去のシフト履歴は残すため物理削除はしない。
	 *
	 * @param int $id
	 * @return int  更新件数
	 */
	public static function soft_delete($id)
	{
		return \DB::update('employees')
			->set(array('deleted_at' => \DB::expr('NOW()')))
			->where('id', $id)
			->execute();
	}

	/**
	 * メールアドレスの重複確認（UNIQUE制約に頼らず事前に確認する）
	 *
	 * @param string   $email
	 * @param int|null $exclude_id  編集時に自分自身を除外する
	 * @return bool
	 */
	public static function email_exists($email, $exclude_id = null)
	{
		$query = \DB::select('id')
			->from('employees')
			->where('email', $email)
			->where('deleted_at', null);

		if ($exclude_id !== null)
		{
			$query->where('id', '!=', $exclude_id);
		}

		// 該当なしのとき current() は null を返すため、真偽値で判定する
		return (bool) $query->execute()->current();
	}

	/**
	 * 一覧・件数で共通の絞り込み条件
	 *
	 * @param object $query
	 * @param array  $filters
	 */
	private static function apply_filters($query, array $filters)
	{
		if ( ! empty($filters['department_id']))
		{
			$query->where('employees.department_id', (int) $filters['department_id']);
		}

		if ( ! empty($filters['employment_type']))
		{
			$query->where('employees.employment_type', $filters['employment_type']);
		}
	}
}
