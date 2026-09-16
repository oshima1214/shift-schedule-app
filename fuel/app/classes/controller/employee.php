<?php
/**
 * S05 従業員管理画面（管理者）
 * F06 登録 / F07 編集 / F08 削除 / F11 絞り込み
 */
class Controller_Employee extends Controller_Base
{
  public function before()
  {
    parent::before();

    $this->require_admin();
  }

  public function action_index()
  {
    $view = \View::forge('employee/index');
    $view->set('employee', $this->current_employee);
    $view->set('departments', \App\Model\Department::find_all());
    $view->set('employment_types', \Config::get('shift.employment_type'));
    $view->set('roles', \Config::get('shift.role'));
    $view->set('password_min_length', (int) \Config::get('shift.password.min_length'));

    return \Response::forge($view);
  }

  /**
   * 絞り込み・ページング付きの従業員一覧
   */
  public function action_list()
  {
    $per_page = (int) \Config::get('shift.per_page');
    $page     = max(1, (int) \Input::get('page', 1));

    $filters = array(
      'department_id'   => \Input::get('department_id'),
      'employment_type' => \Input::get('employment_type'),
    );

    $total       = \App\Model\Employee::count_filtered($filters);
    $total_pages = max(1, (int) ceil($total / $per_page));
    $page        = min($page, $total_pages);

    $employees = \App\Model\Employee::find_paged($filters, $page, $per_page);

    $employment_types = \Config::get('shift.employment_type');
    $roles            = \Config::get('shift.role');

    $rows = array();
    foreach ($employees as $row)
    {
      $rows[] = array(
        'id'                    => (int) $row['id'],
        'name'                  => $row['name'],
        'email'                 => $row['email'],
        'department_id'         => (int) $row['department_id'],
        'department_name'       => $row['department_name'],
        'employment_type'       => $row['employment_type'],
        'employment_type_label' => \Arr::get($employment_types, $row['employment_type'], $row['employment_type']),
        'role'                  => $row['role'],
        'role_label'            => \Arr::get($roles, $row['role'], $row['role']),
        'is_self'               => (int) $row['id'] === (int) $this->current_employee['id'],
      );
    }

    return $this->json(array(
      'rows'        => $rows,
      'page'        => $page,
      'total_pages' => $total_pages,
      'total'       => $total,
    ));
  }

  public function action_create()
  {
    $this->require_post();

    list($data, $errors) = $this->validate_input();

    if ( ! empty($errors))
    {
      return $this->json_errors($errors);
    }

    if ($data['password'] === '')
    {
      return $this->json_errors(array('パスワードを入力してください。'));
    }

    return $this->json(array('id' => \App\Model\Employee::create($data)), 201);
  }

  public function action_update($id = null)
  {
    $this->require_post();

    $id     = (int) $id;
    $target = $id > 0 ? \App\Model\Employee::find($id) : null;

    if ($target === null)
    {
      return $this->json(array('errors' => array('対象の従業員が見つかりません。')), 404);
    }

    list($data, $errors) = $this->validate_input($id);

    if ( ! empty($errors))
    {
      return $this->json_errors($errors);
    }

    // 自分自身の権限を管理者以外に変更すると管理画面から締め出されるため防ぐ
    if ($id === (int) $this->current_employee['id'] and $data['role'] !== 'admin')
    {
      return $this->json_errors(array('自分自身の権限は変更できません。'));
    }

    \App\Model\Employee::update($id, $data);

    return $this->json(array('id' => $id));
  }

  /**
   * 論理削除。過去のシフト履歴は残す。
   *
   * @param int $id
   */
  public function action_delete($id = null)
  {
    $this->require_post();

    $id     = (int) $id;
    $target = $id > 0 ? \App\Model\Employee::find($id) : null;

    if ($target === null)
    {
      return $this->json(array('errors' => array('対象の従業員が見つかりません。')), 404);
    }

    if ($id === (int) $this->current_employee['id'])
    {
      return $this->json_errors(array('自分自身は削除できません。'));
    }

    \App\Model\Employee::soft_delete($id);

    return $this->json(array('id' => $id));
  }

  /**
   * 入力値の検証（サーバサイドで必ず行う）
   *
   * @param int|null $exclude_id  編集時の自分自身のID
   * @return array [array $data, array $errors]
   */
  private function validate_input($exclude_id = null)
  {
    $errors = array();

    $name            = trim((string) \Input::json('name', ''));
    $email           = trim((string) \Input::json('email', ''));
    $department_id   = (int) \Input::json('department_id', 0);
    $employment_type = (string) \Input::json('employment_type', '');
    $role            = (string) \Input::json('role', '');
    $password        = (string) \Input::json('password', '');

    if ($name === '' or mb_strlen($name) > 100)
    {
      $errors[] = '氏名は1〜100文字で入力してください。';
    }

    if ($email === '' or mb_strlen($email) > 255 or ! filter_var($email, FILTER_VALIDATE_EMAIL))
    {
      $errors[] = 'メールアドレスの形式が正しくありません。';
    }
    elseif (\App\Model\Employee::email_exists($email, $exclude_id))
    {
      $errors[] = 'このメールアドレスは既に登録されています。';
    }

    if (\App\Model\Department::find($department_id) === null)
    {
      $errors[] = '所属部署を選択してください。';
    }

    if ( ! array_key_exists($employment_type, \Config::get('shift.employment_type')))
    {
      $errors[] = '雇用形態を選択してください。';
    }

    if ( ! array_key_exists($role, \Config::get('shift.role')))
    {
      $errors[] = '権限を選択してください。';
    }

    $min_length = (int) \Config::get('shift.password.min_length');
    $max_length = (int) \Config::get('shift.password.max_length');

    if ($password !== '' and (mb_strlen($password) < $min_length or mb_strlen($password) > $max_length))
    {
      $errors[] = 'パスワードは'.$min_length.'〜'.$max_length.'文字で入力してください。';
    }

    return array(
      array(
        'name'            => $name,
        'email'           => $email,
        'department_id'   => $department_id,
        'employment_type' => $employment_type,
        'role'            => $role,
        'password'        => $password,
      ),
      $errors,
    );
  }
}
