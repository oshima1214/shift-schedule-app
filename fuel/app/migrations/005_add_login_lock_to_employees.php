<?php

namespace Fuel\Migrations;

/**
 * ログイン試行回数の制限に使う列を追加する。
 * 総当たり攻撃を防ぐため、連続失敗が一定回数に達したアカウントを一時的にロックする。
 */
class Add_login_lock_to_employees
{
	public function up()
	{
		\DBUtil::add_fields('employees', array(
			// 連続で失敗した回数。ログイン成功／ロック時に0へ戻す。
			'failed_login_count' => array(
				'type'       => 'int',
				'constraint' => 11,
				'default'    => 0,
				'after'      => 'password_hash',
			),
			// この日時までログインを受け付けない。nullならロックなし。
			'locked_until' => array(
				'type'  => 'datetime',
				'null'  => true,
				'after' => 'failed_login_count',
			),
		));
	}

	public function down()
	{
		\DBUtil::drop_fields('employees', array('failed_login_count', 'locked_until'));
	}
}
