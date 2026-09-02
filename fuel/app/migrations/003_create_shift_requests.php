<?php

namespace Fuel\Migrations;

class Create_shift_requests
{
	public function up()
	{
		\DBUtil::create_table('shift_requests', array(
			'id'          => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'employee_id' => array('type' => 'int', 'constraint' => 11),
			'work_date'   => array('type' => 'date'),
			'start_time'  => array('type' => 'time'),
			'end_time'    => array('type' => 'time'),
			// 状態 requested:希望中 / approved:確定 / rejected:却下
			'status'      => array('type' => 'char', 'constraint' => 10, 'default' => 'requested'),
			'created_at'  => array('type' => 'timestamp', 'default' => \DB::expr('CURRENT_TIMESTAMP')),
			'updated_at'  => array('type' => 'timestamp', 'default' => \DB::expr('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')),
			'deleted_at'  => array('type' => 'timestamp', 'null' => true),
		), array('id'), false, 'InnoDB', 'utf8mb4', array(
			array(
				'key'       => 'employee_id',
				'reference' => array('table' => 'employees', 'column' => 'id'),
				'on_update' => 'CASCADE',
				'on_delete' => 'RESTRICT',
			),
		));

		\DBUtil::create_index('shift_requests', array('work_date', 'employee_id'), 'idx_shift_requests_date_employee');
		\DBUtil::create_index('shift_requests', 'deleted_at', 'idx_shift_requests_deleted_at');

		// デモ用データ。今週の月曜を起点に投入し、初回表示で必ず見えるようにする。
		$monday = new \DateTime('monday this week');

		// array(メールアドレス, 月曜からの日数, 開始, 終了, 状態)
		$rows = array(
			array('yamada@example.com', 0, '09:00', '13:00', 'requested'),
			array('yamada@example.com', 1, '17:00', '22:00', 'approved'),
			array('yamada@example.com', 3, '09:00', '13:00', 'requested'),
			array('yamada@example.com', 5, '09:00', '18:00', 'approved'),
			array('sato@example.com',   1, '10:00', '15:00', 'requested'),
			array('sato@example.com',   2, '10:00', '15:00', 'approved'),
			array('sato@example.com',   4, '17:00', '22:00', 'requested'),
			array('sato@example.com',   6, '09:00', '18:00', 'approved'),
			array('suzuki@example.com', 0, '13:00', '18:00', 'approved'),
			array('suzuki@example.com', 2, '13:00', '18:00', 'requested'),
			array('suzuki@example.com', 3, '09:00', '13:00', 'rejected'),
			array('suzuki@example.com', 6, '09:00', '14:00', 'requested'),
		);

		foreach ($rows as $row)
		{
			list($email, $offset, $start_time, $end_time, $status) = $row;

			$work_date = (clone $monday)->modify('+'.$offset.' days');

			\DB::insert('shift_requests')->set(array(
				'employee_id' => static::employee_id($email),
				'work_date'   => $work_date->format('Y-m-d'),
				'start_time'  => $start_time,
				'end_time'    => $end_time,
				'status'      => $status,
			))->execute();
		}
	}

	public function down()
	{
		\DBUtil::drop_table('shift_requests');
	}

	/**
	 * メールアドレスから従業員IDを引く
	 *
	 * @param string $email
	 * @return int
	 */
	private static function employee_id($email)
	{
		$row = \DB::select('id')->from('employees')->where('email', $email)->execute()->current();

		return (int) $row['id'];
	}
}
