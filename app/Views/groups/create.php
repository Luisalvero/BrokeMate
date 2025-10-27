<div class="card" style="max-width:560px;margin:0 auto;">
  <h2>Create Group</h2>
  <form method="post" action="/groups/create">
    <?= App\Lib\CSRF::field() ?>
    <div class="field"><label>Name</label><input class="input" name="name" required></div>
    <div class="field"><label>Description</label><textarea class="input" name="description"></textarea></div>
    <div class="field"><label>Currency</label><input class="input" name="currency" value="<?= App\Lib\Util::e(App\Lib\Auth::user()['currency'] ?? 'USD') ?>"></div>
    <button class="btn">Create</button>
  </form>
</div>
