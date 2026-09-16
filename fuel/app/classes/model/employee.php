<?php

namespace App\Model;

class Employee
{
	/**
	 * email + 平文パスワードで認証する。
	 *
	 * 総当たり攻撃対策として、連続で失敗した回数を数え、
	 * 上限に達したアカウントは一定時間ログインを受け付けない。
	 *
	 * @param string $email
	 * @param string $password
	 * @return array  array('status' => 'ok'|'invalid'|'locked', 'employee' => array|null, 'lock_seconds' => int)
	 */
	public static function authenticate($email, $password)
	{
		// ロックの判定は日時の比較になるため、他の日時列（deleted_atなど）と
		// 同じDB側の時計で揃える。そのため現在時刻もDBから受け取る。
		$row = \DB::select(
				'id', 'department_id', 'name', 'email', 'password_hash',
				'employment_type', 'role', 'failed_login_count', 'locked_until',
				array(\DB::expr('NOW()'), 'db_now')
			)
			->from('employees')
			->where('email', $email)
			->where('deleted_at', null)
			->execute()
			->current();

		// 該当なしのとき current() は null を返すため、真偽値で判定する。
		// 存在しないメールアドレスかどうかを画面に出さないよう、扱いは失敗と同じにする。
		if ( ! $row)
		{
			return static::auth_result('invalid');
		}

		$lock_seconds = static::lock_seconds($row);

		if ($lock_seconds > 0)
		{
			return static::auth_result('locked', null, $lock_seconds);
		}

		if ( ! password_verify($password, $row['password_hash']))
		{
			// この失敗でロックに達したときは、その旨を伝えて再試行をやめさせる
			$lock_seconds = static::register_login_failure($row);

			return $lock_seconds > 0
				? static::auth_result('locked', null, $lock_seconds)
				: static::auth_result('invalid');
		}

		static::clear_login_failure((int) $row['id']);

		unset($row['password_hash'], $row['failed_login_count'], $row['locked_until'], $row['db_now']);

		return static::auth_result('ok', $row);
	}

	/**
	 * 本人によるパスワード変更のために、現在のパスワードを照合する
	 *
	 * @param int    $id
	 * @param string $password
	 * @return bool
	 */
	public static function verify_password($id, $password)
	{
		$row = \DB::select('password_hash')
			->from('employees')
			->where('id', $id)
			->where('deleted_at', null)
			->execute()
			->current();

		return $row ? password_verify($password, $row['password_hash']) : false;
	}

	/**
	 * パスワードだけを更新する。あわせてログイン失敗の記録も消す
	 * （パスワードを変えた本人が、ロックの残り時間を待たされないようにする）。
	 *
	 * @param int    $id
	 * @param string $password
	 * @return int  更新件数
	 */
	public static function update_password($id, $password)
	{
		return \DB::update('employees')
			->set(array(
				'password_hash'      => password_hash($password, PASSWORD_DEFAULT),
				'failed_login_count' => 0,
				'locked_until'       => null,
			))
			->where('id', $id)
			->execute();
	}

	/**
	 * ロック解除までの残り秒数。ロックしていない・期限切れなら0。
	 * 期限切れのロックはロックとして扱わない（次の失敗で数え直す）。
	 *
	 * @param array $row  locked_at と db_now を含む行
	 * @return int
	 */
	private static function lock_seconds(array $row)
	{
		if (empty($row['locked_until']))
		{
			return 0;
		}

		// どちらもDB側の時計の文字列なので、同じ扱いで比較できる
		$remain = strtotime($row['locked_until']) - strtotime($row['db_now']);

		return $remain > 0 ? $remain : 0;
	}

	/**
	 * ログイン失敗を1回記録する。上限に達したらロックする。
	 *
	 * @param array $row
	 * @return int  ロックした場合は解除までの秒数、しなければ0
	 */
	private static function register_login_failure(array $row)
	{
		$max     = (int) \Config::get('shift.login.max_attempts');
		$minutes = (int) \Config::get('shift.login.lockout_minutes');

		// 期限切れのロックが残っている場合は、そこから1回目として数え直す
		$expired = ! empty($row['locked_until']);
		$count   = $expired ? 1 : ((int) $row['failed_login_count'] + 1);

		$values  = array('failed_login_count' => $count, 'locked_until' => null);
		$seconds = 0;

		if ($count >= $max)
		{
			// 日時はDB側の時計で作る（他の日時列と揃える）。
			// 埋め込むのは設定値をintにキャストした分数のみで、入力値は含まない。
			$values['locked_until'] = \DB::expr('DATE_ADD(NOW(), INTERVAL '.$minutes.' MINUTE)');
			// ロック解除後はまた0回目から数える
			$values['failed_login_count'] = 0;

			$seconds = $minutes * 60;
		}

		\DB::update('employees')->set($values)->where('id', $row['id'])->execute();

		return $seconds;
	}

	/**
	 * ログイン成功時に失敗の記録を消す
	 *
	 * @param int $id
	 */
	private static function clear_login_failure($id)
	{
		\DB::update('employees')
			->set(array('failed_login_count' => 0, 'locked_until' => null))
			->where('id', $id)
			->execute();
	}

	/**
	 * authenticate() の戻り値を組み立てる
	 *
	 * @param string     $status
	 * @param array|null $employee
	 * @param int        $lock_seconds  ロック解除までの残り秒数
	 * @return array
	 */
	private static function auth_result($status, $employee = null, $lock_seconds = 0)
	{
		return array(
			'status'       => $status,
			'employee'     => $employee,
			'lock_seconds' => (int) $lock_seconds,
		);
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

			// 管理者がパスワードを再設定したら、ログイン失敗によるロックも解除する
			$values['failed_login_count'] = 0;
			$values['locked_until']       = null;
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
