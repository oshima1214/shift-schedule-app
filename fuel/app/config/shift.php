<?php
/**
 * アプリ固有の設定値
 */

return array(

	// シフト希望として登録できる勤務日の範囲（本日から何日先まで）
	'request_range_days' => 60,

	// シフト希望のステータス
	'status' => array(
		'pending'   => '希望中',
		'confirmed' => '確定',
	),

	// ログインセッションのキー名
	'session_key' => 'employee_id',
);
