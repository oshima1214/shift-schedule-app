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
			'status'      => array('type' => 'enum', 'constraint' => "'pending','confirmed'", 'default' => 'pending'),
			'note'        => array('type' => 'varchar', 'constraint' => 255, 'null' => true),
			'created_at'  => array('type' => 'datetime', 'null' => true),
			'updated_at'  => array('type' => 'datetime', 'null' => true),
			'deleted_at'  => array('type' => 'datetime', 'null' => true),
		), array('id'), false, 'InnoDB', 'utf8mb4', array(
			array(
				'key'       => 'employee_id',
				'reference' => array('table' => 'employees', 'column' => 'id'),
				'on_update' => 'CASCADE',
				'on_delete' => 'RESTRICT',
			),
		));

		\DBUtil::create_index('shift_requests', array('employee_id', 'work_date'), 'idx_shift_requests_employee_date');
		\DBUtil::create_index('shift_requests', 'deleted_at', 'idx_shift_requests_deleted_at');
	}

	public function down()
	{
		\DBUtil::drop_table('shift_requests');
	}
}
