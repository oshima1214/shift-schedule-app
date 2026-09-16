<?php
/**
 * 共通ヘッダ。$employee（ログイン中の従業員）と $active（現在の画面）を受け取る。
 * 管理者にだけ管理メニューを出すが、実際の制限は各コントローラのbefore()で行う。
 */
$menu = array();

if ($employee['role'] === 'admin')
{
	$menu = array(
		'request'  => array('url' => 'request',  'label' => 'シフト希望一覧'),
		'schedule' => array('url' => 'schedule', 'label' => 'シフト表確定'),
		'employee' => array('url' => 'employee', 'label' => '従業員管理'),
	);
}
else
{
	$menu = array(
		'shift' => array('url' => 'shift', 'label' => 'シフト希望入力'),
	);
}

// パスワード変更は権限によらず本人が使う
$menu['account'] = array('url' => 'account/password', 'label' => 'パスワード変更');
?>
<header class="appbar">
	<div class="appbar-inner">
		<span class="appbar-title">シフト表作成アプリ</span>

		<nav class="nav">
			<?php foreach ($menu as $key => $item): ?>
				<a href="<?php echo Uri::create($item['url']); ?>"<?php echo $key === $active ? ' class="active"' : ''; ?>><?php echo $item['label']; ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="appbar-user">
			<span><?php echo $employee['name']; ?><?php echo $employment_label === '' ? '' : '（'.$employment_label.'）'; ?></span>
			<?php echo Form::open(array('action' => 'auth/logout', 'method' => 'post')); ?>
				<button type="submit" class="btn-link">ログアウト</button>
			<?php echo Form::close(); ?>
		</div>
	</div>
</header>
