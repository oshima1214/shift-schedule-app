<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>パスワード変更｜シフト表作成アプリ</title>
<link rel="stylesheet" href="<?php echo Uri::create('assets/css/app.css'); ?>">
</head>
<body>

<?php echo View::forge('partial/header', array(
  'employee'         => $employee,
  'employment_label' => $employment_label,
  'active'           => 'account',
  'menu'             => $menu,
)); ?>

<div class="wrap narrow">
  <h1>パスワード変更</h1>
  <p class="lead">ログイン中のアカウント（<?php echo $employee['email']; ?>）のパスワードを変更します。</p>

  <div class="screen">
    <?php if ($success): ?>
      <div class="notice">パスワードを変更しました。次回のログインから新しいパスワードを使用してください。</div>
    <?php endif; ?>

    <?php if ( ! empty($errors)): ?>
      <div class="errors">
        <ul>
          <?php foreach ($errors as $message): ?>
            <li><?php echo $message; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php echo Form::open(array('action' => 'account/password', 'method' => 'post', 'autocomplete' => 'off')); ?>
      <div class="form-body">
        <div class="field">
          <label class="field-label" for="current_password">現在のパスワード <span class="req">*</span></label>
          <input class="inp" type="password" id="current_password" name="current_password"
            autocomplete="current-password" required>
        </div>

        <div class="field">
          <label class="field-label" for="new_password">新しいパスワード <span class="req">*</span></label>
          <input class="inp" type="password" id="new_password" name="new_password"
            autocomplete="new-password" required>
          <div class="hint"><?php echo (int) $min_length; ?>文字以上で、現在のパスワードとは違うものを設定してください。</div>
        </div>

        <div class="field">
          <label class="field-label" for="new_password_confirm">新しいパスワード（確認用） <span class="req">*</span></label>
          <input class="inp" type="password" id="new_password_confirm" name="new_password_confirm"
            autocomplete="new-password" required>
        </div>
      </div>

      <div class="modal-foot">
        <button type="submit" class="btn btn-primary">変更する</button>
      </div>
    <?php echo Form::close(); ?>
  </div>
</div>

</body>
</html>
