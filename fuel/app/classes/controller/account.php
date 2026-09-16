<?php
/**
 * S06 パスワード変更画面（本人）
 * F14 パスワード変更
 *
 * 管理者が発行したパスワードを本人が変更できるようにする。
 * 変更できるのは常に「ログイン中の本人」のみで、他人のIDは受け付けない。
 */
class Controller_Account extends Controller_Base
{
  public function action_password()
  {
    $errors  = array();
    $success = false;

    if (\Input::method() === 'POST')
    {
      $errors = $this->change_password();

      $success = empty($errors);
    }

    $view = \View::forge('account/password');
    $view->set('employee', $this->current_employee);
    $view->set('menu', $this->nav_menu());
    // 管理者に雇用形態を出しても意味がないため、他の管理画面と同じく空にする
    $view->set('employment_label', $this->current_employee['role'] === 'admin'
      ? ''
      : \Arr::get(\Config::get('shift.employment_type'), $this->current_employee['employment_type'], ''));
    $view->set('errors', $errors);
    $view->set('success', $success);
    $view->set('min_length', (int) \Config::get('shift.password.min_length'));

    return \Response::forge($view);
  }

  /**
   * 入力を検証してパスワードを更新する
   *
   * @return array  エラーメッセージ（空なら成功）
   */
  private function change_password()
  {
    $current = (string) \Input::post('current_password', '');
    $new     = (string) \Input::post('new_password', '');
    $confirm = (string) \Input::post('new_password_confirm', '');

    $min = (int) \Config::get('shift.password.min_length');
    $max = (int) \Config::get('shift.password.max_length');

    $errors = array();

    // なりすましたセッションで勝手に変更されないよう、現在のパスワードを必ず確かめる
    if ($current === '')
    {
      $errors[] = '現在のパスワードを入力してください。';
    }
    elseif ( ! \App\Model\Employee::verify_password($this->current_employee['id'], $current))
    {
      $errors[] = '現在のパスワードが正しくありません。';
    }

    if ($new === '')
    {
      $errors[] = '新しいパスワードを入力してください。';
    }
    elseif (mb_strlen($new) < $min or mb_strlen($new) > $max)
    {
      $errors[] = '新しいパスワードは'.$min.'〜'.$max.'文字で入力してください。';
    }
    elseif ($new === $current)
    {
      $errors[] = '新しいパスワードは現在のパスワードと異なるものにしてください。';
    }
    elseif ($new !== $confirm)
    {
      $errors[] = '新しいパスワード（確認用）が一致しません。';
    }

    if ( ! empty($errors))
    {
      return $errors;
    }

    \App\Model\Employee::update_password($this->current_employee['id'], $new);

    // 認証情報が変わるタイミングなので、セッションIDを再発行しておく
    \Session::rotate();

    return array();
  }
}
