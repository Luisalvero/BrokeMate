<div class="card" style="max-width:480px;margin:20px auto;">
  <h2>Register</h2>
  <form method="post" action="/register">
    <?= App\Lib\CSRF::field() ?>
    <div class="field"><label>Name</label><input class="input" name="name" value="<?= App\Lib\Util::e($old['name'] ?? '') ?>" required></div>
    <div class="field"><label>Email</label><input class="input" name="email" type="email" value="<?= App\Lib\Util::e($old['email'] ?? '') ?>" required></div>
    <div class="field"><label>Password</label><input class="input" name="password" type="password" required></div>
    <div class="field"><label>Currency</label><input class="input" name="currency" value="USD"></div>
    <button class="btn">Create account</button>
  </form>
</div>
