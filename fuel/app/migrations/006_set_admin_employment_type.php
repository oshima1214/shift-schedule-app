<?php

namespace Fuel\Migrations;

/**
 * 雇用形態に full_time（正社員）を追加したのに合わせて、
 * デモ用の管理者アカウントを正社員にする。
 *
 * 管理者がアルバイト扱いのままだと従業員管理の一覧で違和感があるため。
 * 列の定義（char(10)）は変わらず、選択肢は config/shift.php で持っている。
 */
class Set_admin_employment_type
{
  public function up()
  {
    \DB::update('employees')
      ->set(array('employment_type' => 'full_time'))
      ->where('email', 'admin@example.com')
      ->execute();
  }

  public function down()
  {
    \DB::update('employees')
      ->set(array('employment_type' => 'part_time'))
      ->where('email', 'admin@example.com')
      ->execute();
  }
}
