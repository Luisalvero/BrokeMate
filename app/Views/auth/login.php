<div class="card" style="max-width:420px;margin:20px auto;">
  <h2>Login</h2>
  <form method="post" action="/login">
    <?= App\Lib\CSRF::field() ?>
    <div class="field"><label>Email</label><input class="input" name="email" type="email" required></div>
    <div class="field"><label>Password</label><input class="input" name="password" type="password" required></div>
    <button class="btn">Sign in</button>
  </form>
  <p class="muted">No account? <a href="/register">Register</a></p>
</div>
