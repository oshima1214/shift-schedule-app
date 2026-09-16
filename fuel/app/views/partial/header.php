<?php
/**
 * 共通ヘッダ。
 * $menu（Controller_Base::nav_menu() が組み立てたもの）、
 * $employee（ログイン中の従業員）、$active（現在の画面）を受け取って出力するだけ。
 */
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
