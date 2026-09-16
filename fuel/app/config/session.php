<?php
/**
 * セッションの設定。
 *
 * IPA「安全なウェブサイトの作り方」のセッション管理に合わせ、
 * セッションIDを保持するCookieにHttpOnly属性を付ける。
 * （JavaScriptから読めなくすることで、XSSが起きた場合の被害を抑える）
 */

return array(

  // JavaScriptからCookieを読めなくする
  'cookie_http_only' => true,

  // HTTPSでのみCookieを送る設定。開発環境はHTTPなのでfalseにしておき、
  // 本番（HTTPS）では fuel/app/config/production/session.php で true にする。
  'cookie_secure' => false,

);
