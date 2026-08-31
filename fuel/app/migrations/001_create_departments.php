<?php

namespace Fuel\Migrations;

class Create_departments
{
	public function up()
	{
		\DBUtil::create_table('departments', array(
			'id'         => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
			'name'       => array('type' => 'varchar', 'constraint' => 100),
			'created_at' => array('type' => 'datetime', 'null' => true),
			'updated_at' => array('type' => 'datetime', 'null' => true),
			'deleted_at' => array('type' => 'datetime', 'null' => true),
		), array('id'), false, 'InnoDB', 'utf8mb4');

		\DBUtil::create_index('departments', 'deleted_at', 'idx_departments_deleted_at');

		\DB::insert('departments')
			->set(array('name' => '本社', 'created_at' => \DB::expr('NOW()'), 'updated_at' => \DB::expr('NOW()')))
			->execute();
	}

	public function down()
	{
		\DBUtil::drop_table('departments');
	}
}
