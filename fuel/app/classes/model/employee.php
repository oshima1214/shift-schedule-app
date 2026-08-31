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
	public static function authenticate(string $email, string $password)
	{
		$row = \DB::select('id', 'department_id', 'name', 'email', 'password_hash', 'role')
			->from('employees')
			->where('email', $email)
			->where('deleted_at', null)
			->execute()
			->current();

		if ($row === false)
		{
			return null;
		}

		if ( ! password_verify($password, $row['password_hash']))
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
	public static function find(int $id)
	{
		$row = \DB::select('id', 'department_id', 'name', 'email', 'role')
			->from('employees')
			->where('id', $id)
			->where('deleted_at', null)
			->execute()
			->current();

		return $row === false ? null : $row;
	}
}
