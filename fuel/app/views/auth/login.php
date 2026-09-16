<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ログイン｜シフト表作成アプリ</title>
<link rel="stylesheet" href="<?php echo Uri::create('assets/css/app.css'); ?>">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-title">シフト表作成アプリ</div>

    <?php if ($error): ?>
      <div class="errors errors-boxed"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php echo Form::open(array('action' => 'auth/login', 'method' => 'post')); ?>
      <label class="field-label" for="email">メールアドレス</label>
      <input class="inp" type="email" id="email" name="email" required autofocus>

      <label class="field-label" for="password">パスワード</label>
      <input class="inp" type="password" id="password" name="password" required>

      <button type="submit" class="btn btn-primary">ログイン</button>
    <?php echo Form::close(); ?>

    <p class="login-hint">
      ログインに<?php echo $max_attempts; ?>回続けて失敗すると、
      そのアカウントを<?php echo $lockout_minutes; ?>分間ロックします。
    </p>

    <p class="login-hint">
      デモ用アカウント<br>
      管理者：admin@example.com ／ Admin#12345<br>
      従業員：yamada@example.com ／ Staff#12345
    </p>
  </div>
</div>
</body>
</html>
