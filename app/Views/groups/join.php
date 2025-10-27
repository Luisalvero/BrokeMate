<div class="card" style="max-width:420px;margin:0 auto;">
  <h2>Join Group</h2>
  <form method="post" action="/groups/join">
    <?= App\Lib\CSRF::field() ?>
    <div class="field"><label>Invite Code</label><input class="input" name="code" placeholder="BM-ABCD12" required></div>
    <button class="btn">Join</button>
  </form>
</div>
