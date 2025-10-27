<?php
use App\Lib\Auth; use App\Lib\CSRF; use App\Lib\Util;
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= Util::e(CSRF::token()) ?>">
  <title>BrokeMate</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <header>
    <nav>
      <div><a href="/" class="logo"><strong>BrokeMate</strong></a></div>
      <div class="nav-links">
        <?php if (Auth::check()): ?>
          <a href="/dashboard">Dashboard</a>
          <a href="/groups/create">Create Group</a>
          <a href="/groups/join">Join</a>
          <a href="/notifications" class="bell" id="notify-bell">🔔<?php
            $pdo = App\Lib\DB::conn();
            $uid = Auth::user()['id'] ?? 0;
            $n = $pdo->query('SELECT COUNT(*) c FROM notifications WHERE user_id='.(int)$uid.' AND is_read=0')->fetchColumn();
            if ($n) echo '<span class="dot">'.(int)$n.'</span>';
          ?></a>
          <a href="/settings/profile">Settings</a>
          <a href="/logout" class="right">Logout</a>
        <?php else: ?>
          <a href="/login">Login</a>
          <a href="/register">Register</a>
        <?php endif; ?>
        <button class="btn secondary" id="theme-toggle" type="button">Theme</button>
      </div>
    </nav>
  </header>
  <div class="container">
    <?php foreach (($flash ?? []) as $f): ?>
      <div class="flash <?= Util::e($f['type']) ?>"><?= Util::e($f['message']) ?></div>
    <?php endforeach; ?>
    <?php include $viewFile; ?>
  </div>
  <footer>
    <div class="container muted">&copy; <?= date('Y') ?> BrokeMate</div>
  </footer>
  <script src="/assets/js/app.js"></script>
</body>
</html>
