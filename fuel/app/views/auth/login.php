<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>ログイン - シフト表作成アプリ</title>
<style>
	body { font-family: sans-serif; background: #f4f5f7; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
	.card { background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 320px; }
	h1 { font-size: 20px; margin: 0 0 20px; }
	label { display: block; font-size: 13px; margin-bottom: 4px; color: #555; }
	input[type=email], input[type=password] { width: 100%; padding: 8px; margin-bottom: 16px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
	button { width: 100%; padding: 10px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
	.error { color: #c0392b; font-size: 13px; margin-bottom: 12px; }
	.hint { margin-top: 16px; font-size: 12px; color: #888; }
</style>
</head>
<body>
	<div class="card">
		<h1>シフト表作成アプリ ログイン</h1>

		<?php if ($error): ?>
			<p class="error"><?php echo $error; ?></p>
		<?php endif; ?>

		<?php echo \Form::open(array('action' => 'auth/login', 'method' => 'post')); ?>
			<label for="email">メールアドレス</label>
			<input type="email" id="email" name="email" required autofocus>

			<label for="password">パスワード</label>
			<input type="password" id="password" name="password" required>

			<button type="submit">ログイン</button>
		<?php echo \Form::close(); ?>

		<p class="hint">
			デモ用アカウント<br>
			管理者: admin@example.com / Admin#12345<br>
			スタッフ: staff@example.com / Staff#12345
		</p>
	</div>
</body>
</html>
