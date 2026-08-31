<?php

namespace Fuel\Migrations;

class Create_employees
{
	public function up()
	{
		\DBUtil::create_table('employees', array(
			'id'            => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'department_id' => array('type' => 'int', 'constraint' => 11),
			'name'          => array('type' => 'varchar', 'constraint' => 100),
			'email'         => array('type' => 'varchar', 'constraint' => 255),
			'password_hash' => array('type' => 'varchar', 'constraint' => 255),
			'role'          => array('type' => 'enum', 'constraint' => "'admin','staff'", 'default' => 'staff'),
			'created_at'    => array('type' => 'datetime', 'null' => true),
			'updated_at'    => array('type' => 'datetime', 'null' => true),
			'deleted_at'    => array('type' => 'datetime', 'null' => true),
		), array('id'), false, 'InnoDB', 'utf8mb4', array(
			array(
				'key'       => 'department_id',
				'reference' => array('table' => 'departments', 'column' => 'id'),
				'on_update' => 'CASCADE',
				'on_delete' => 'RESTRICT',
			),
		));

		\DBUtil::create_index('employees', 'email', 'idx_employees_email', 'UNIQUE');
		\DBUtil::create_index('employees', 'deleted_at', 'idx_employees_deleted_at');

		// デモ用アカウント（パスワードは password_hash() でハッシュ化して登録）
		$now = \DB::expr('NOW()');

		\DB::insert('employees')->set(array(
			'department_id' => 1,
			'name'          => '管理者 太郎',
			'email'         => 'admin@example.com',
			'password_hash' => password_hash('Admin#12345', PASSWORD_DEFAULT),
			'role'          => 'admin',
			'created_at'    => $now,
			'updated_at'    => $now,
		))->execute();

		\DB::insert('employees')->set(array(
			'department_id' => 1,
			'name'          => '山田 花子',
			'email'         => 'staff@example.com',
			'password_hash' => password_hash('Staff#12345', PASSWORD_DEFAULT),
			'role'          => 'staff',
			'created_at'    => $now,
			'updated_at'    => $now,
		))->execute();
	}

	public function down()
	{
		\DBUtil::drop_table('employees');
	}
}
