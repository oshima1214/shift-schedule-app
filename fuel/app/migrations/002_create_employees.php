<?php

namespace Fuel\Migrations;

class Create_employees
{
  public function up()
  {
    \DBUtil::create_table('employees', array(
      'id'              => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
      'department_id'   => array('type' => 'int', 'constraint' => 11),
      'name'            => array('type' => 'varchar', 'constraint' => 100),
      'email'           => array('type' => 'varchar', 'constraint' => 255),
      'password_hash'   => array('type' => 'varchar', 'constraint' => 255),
      // 雇用形態 part_time:アルバイト / part:パート
      'employment_type' => array('type' => 'char', 'constraint' => 10, 'default' => 'part_time'),
      // 権限 employee:従業員 / admin:管理者
      'role'            => array('type' => 'char', 'constraint' => 10, 'default' => 'employee'),
      'created_at'      => array('type' => 'timestamp', 'default' => \DB::expr('CURRENT_TIMESTAMP')),
      'updated_at'      => array('type' => 'timestamp', 'default' => \DB::expr('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')),
      'deleted_at'      => array('type' => 'timestamp', 'null' => true),
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

    $hall    = static::department_id('ホール');
    $kitchen = static::department_id('キッチン');

    // デモ用アカウント（パスワードはハッシュ化して保存する）
    $rows = array(
      array('管理者',    'admin@example.com',   $hall,    'part_time', 'admin',    'Admin#12345'),
      array('山田 太郎', 'yamada@example.com',  $hall,    'part_time', 'employee', 'Staff#12345'),
      array('佐藤 花子', 'sato@example.com',    $kitchen, 'part',      'employee', 'Staff#12345'),
      array('鈴木 一郎', 'suzuki@example.com',  $hall,    'part_time', 'employee', 'Staff#12345'),
    );

    foreach ($rows as $row)
    {
      list($name, $email, $department_id, $employment_type, $role, $password) = $row;

      \DB::insert('employees')->set(array(
        'department_id'   => $department_id,
        'name'            => $name,
        'email'           => $email,
        'password_hash'   => password_hash($password, PASSWORD_DEFAULT),
        'employment_type' => $employment_type,
        'role'            => $role,
      ))->execute();
    }
  }

  public function down()
  {
    \DBUtil::drop_table('employees');
  }

  /**
   * 部署名からIDを引く
   *
   * @param string $name
   * @return int
   */
  private static function department_id($name)
  {
    $row = \DB::select('id')->from('departments')->where('name', $name)->execute()->current();

    return (int) $row['id'];
  }
}
