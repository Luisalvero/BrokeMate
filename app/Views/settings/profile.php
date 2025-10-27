<?php $u=$user; ?>
<div class="card" style="max-width:640px;margin:0 auto;">
  <h2>Profile</h2>
  <form method="post" action="/settings/profile">
    <?= App\Lib\CSRF::field() ?>
    <div class="field"><label>Name</label><input class="input" name="name" value="<?= App\Lib\Util::e($u['name']) ?>" required></div>
    <div class="field"><label>Email</label><input class="input" name="email" type="email" value="<?= App\Lib\Util::e($u['email']) ?>" required></div>
    <div class="field"><label>Avatar URL</label><input class="input" name="avatar" value="<?= App\Lib\Util::e($u['avatar'] ?? '') ?>"></div>
    <div class="field"><label>Currency</label><input class="input" name="currency" value="<?= App\Lib\Util::e($u['currency'] ?? 'USD') ?>"></div>
    <button class="btn">Save</button>
  </form>
  <hr>
  <h3>Password</h3>
  <?php if ($u['is_temporary'] ?? 0): ?><p class="muted">Your account is temporary. Set a password to keep it.</p><?php endif; ?>
  <form method="post" action="/settings/password">
    <?= App\Lib\CSRF::field() ?>
    <div class="row">
      <input class="input" type="password" name="password" placeholder="New password" required>
      <button class="btn">Set password</button>
    </div>
  </form>
</div>
