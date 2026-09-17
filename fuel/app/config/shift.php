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

  // 雇用形態。
  // シフトを出すのはアルバイト・パートだが、店長など管理者は社員のことが多いため
  // full_time も選べるようにしている。
  'employment_type' => array(
    'full_time' => '正社員',
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

  // パスワードの文字数制限（登録・変更で共通）
  'password' => array(
    'min_length' => 8,
    'max_length' => 100,
  ),

  // 却下理由の最大文字数（shift_requests.reject_reason の桁数に合わせる）
  'reject_reason_max_length' => 255,

  // 一括操作で一度に処理できる最大件数。
  // 画面に出ている1週間分（従業員数×7日）を十分に上回る値にしておく。
  'bulk_max_count' => 500,

  // ログイン試行回数の制限
  'login' => array(
    // 連続で何回失敗したらロックするか
    'max_attempts' => 5,
    // ロックする時間（分）
    'lockout_minutes' => 15,
  ),
);
