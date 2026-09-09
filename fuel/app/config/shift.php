<?php
/**
 * アプリ固有の設定値
 */

return array(

	// ログインセッションのキー名
	'session_key' => 'employee_id',

	// 従業員管理画面の1ページあたりの表示件数
	'per_page' => 10,

	// シフト希望を登録できる範囲（今週の月曜から何日先まで）
	'request_range_days' => 90,

	// シフト希望の状態
	'status' => array(
		'requested' => '希望中',
		'approved'  => '確定',
		'rejected'  => '却下',
	),

	// 雇用形態
	'employment_type' => array(
		'part_time' => 'アルバイト',
		'part'      => 'パート',
	),

	// 権限
	'role' => array(
		'employee' => '従業員',
		'admin'    => '管理者',
	),

	// シフト希望入力欄で選べる時刻の範囲と刻み（分）
	'time_options' => array(
		'start' => '06:00',
		'end'   => '23:00',
		'step'  => 30,
	),
);
