<?php

namespace Fuel\Migrations;

/**
 * 却下理由を記録できるようにする。
 * 管理者が却下した理由を従業員にも見せるため、シフト希望に列を持たせる。
 */
class Add_reject_reason_to_shift_requests
{
  public function up()
  {
    \DBUtil::add_fields('shift_requests', array(
      // 却下以外の状態では null。状態を戻したときも null に戻す。
      'reject_reason' => array(
        'type'       => 'varchar',
        'constraint' => 255,
        'null'       => true,
        'after'      => 'status',
      ),
    ));

    // 既存のデモデータにも理由を入れておき、初回表示で見えるようにする
    \DB::update('shift_requests')
      ->set(array('reject_reason' => 'この日は他の従業員で人員が足りているため'))
      ->where('status', 'rejected')
      ->where('deleted_at', null)
      ->execute();
  }

  public function down()
  {
    \DBUtil::drop_fields('shift_requests', array('reject_reason'));
  }
}
