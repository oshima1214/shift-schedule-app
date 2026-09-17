<?php

namespace Fuel\Migrations;

class Create_departments
{
  public function up()
  {
    \DBUtil::create_table('departments', array(
      'id'         => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
      'name'       => array('type' => 'varchar', 'constraint' => 100),
      'created_at' => array('type' => 'timestamp', 'default' => \DB::expr('CURRENT_TIMESTAMP')),
      'updated_at' => array('type' => 'timestamp', 'default' => \DB::expr('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')),
      'deleted_at' => array('type' => 'timestamp', 'null' => true),
    ), array('id'), false, 'InnoDB', 'utf8mb4');

    \DBUtil::create_index('departments', 'deleted_at', 'idx_departments_deleted_at');

    foreach (array('ホール', 'キッチン') as $name)
    {
      \DB::insert('departments')->set(array('name' => $name))->execute();
    }
  }

  public function down()
  {
    \DBUtil::drop_table('departments');
  }
}
