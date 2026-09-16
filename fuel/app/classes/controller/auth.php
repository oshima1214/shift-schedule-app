<?php
/**
 * S01 ログイン画面 / F01 ログイン・ログアウト / F18 ログイン試行回数の制限
 * ログイン前でもアクセスできる必要があるため Controller_Base は継承しない。
 */
class Controller_Auth extends \Controller
{
  public function before()
  {
    parent::before();

    // フォームのCSRFトークン準備と検証（詳細は App\Support\Csrf を参照）
    \App\Support\Csrf::prepare();
  }

  /**
   * ログイン画面をブラウザにキャッシュさせない。
   * キャッシュされた古いフォームを再送すると、CSRF検証で弾かれてしまう。
   *
   * @param \Response|string $response
   * @return \Response
   */
  public function after($response)
  {
    $response = parent::after($response);

    $response->set_header('Cache-Control', 'no-store, no-cache, must-revalidate');
    $response->set_header('Pragma', 'no-cache');

    // クリックジャッキング対策。iframeへの埋め込みを一切許可しない。
    $response->set_header('X-Frame-Options', 'DENY');
    // Content-Typeを無視した内容の推測を止めさせる
    $response->set_header('X-Content-Type-Options', 'nosniff');

    return $response;
  }

  /**
   * ログイン画面表示 / ログイン処理
   */
  public function action_login()
  {
    if (\Session::get(\Config::get('shift.session_key')) !== null)
    {
      return \Response::redirect(static::home_for(\Session::get('role')));
    }

    $error = null;

    if (\Input::method() === 'POST')
    {
      $email    = trim((string) \Input::post('email'));
      $password = (string) \Input::post('password');

      $result = \App\Model\Employee::authenticate($email, $password);

      if ($result['status'] === 'locked')
      {
        $error = static::lock_message($result['lock_seconds']);
      }
      elseif ($result['status'] !== 'ok')
      {
        // どちらが誤りかは伝えない（アカウントの存在を推測させないため）
        $error = 'メールアドレスまたはパスワードが正しくありません。';
      }
      else
      {
        $employee = $result['employee'];

        // セッション固定攻撃対策としてログイン成功時にセッションIDを再発行する。
        // 権限が変わるタイミングなのでCSRFトークンもここで作り直す。
        \Session::rotate();
        \Security::set_token(true);

        \Session::set(\Config::get('shift.session_key'), $employee['id']);
        \Session::set('role', $employee['role']);

        return \Response::redirect(static::home_for($employee['role']));
      }
    }

    $view = \View::forge('auth/login');
    $view->set('error', $error);
    $view->set('max_attempts', (int) \Config::get('shift.login.max_attempts'));
    $view->set('lockout_minutes', (int) \Config::get('shift.login.lockout_minutes'));

    return \Response::forge($view);
  }

  /**
   * ロック中であることと、あと何分待てばよいかを伝える
   *
   * @param int $seconds  ロック解除までの残り秒数
   * @return string
   */
  private static function lock_message($seconds)
  {
    $minutes = max(1, (int) ceil($seconds / 60));

    return 'ログインの失敗が続いたため、このアカウントを一時的にロックしています。'
      .'約'.$minutes.'分後に、もう一度お試しください。';
  }

  /**
   * ログアウト（状態変更を伴うためPOSTのみ許可。CSRFはグローバル自動チェック対象）
   */
  public function action_logout()
  {
    if (\Input::method() !== 'POST')
    {
      throw new \HttpNoAccessException('Method not allowed');
    }

    \Session::destroy();

    return \Response::redirect('auth/login');
  }

  /**
   * 権限ごとの最初の画面
   *
   * @param string|null $role
   * @return string
   */
  private static function home_for($role)
  {
    return $role === 'admin' ? 'request' : 'shift';
  }
}
