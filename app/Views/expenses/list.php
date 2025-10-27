<?php $g=$group; ?>
<div class="row" style="justify-content:space-between;align-items:center;">
  <h2 style="margin:0;">Expenses — <?= App\Lib\Util::e($g['name']) ?></h2>
  <div class="row">
    <form method="get" class="row" action="">
      <input class="input" name="q" value="<?= App\Lib\Util::e($q ?? '') ?>" placeholder="Search…">
      <button class="btn secondary" type="submit">Search</button>
    </form>
    <a class="btn" href="/groups/<?= (int)$g['id'] ?>/expenses/create">Add</a>
  </div>
</div>
<div class="card table-scroll">
  <table>
    <tr><th>Date</th><th>Title</th><th>Category</th><th>Payer</th><th>Amount</th></tr>
    <?php foreach ($expenses as $e): ?>
    <tr>
      <td><?= App\Lib\Util::e($e['expense_date']) ?></td>
      <td><?= App\Lib\Util::e($e['title']) ?></td>
      <td><span class="badge"><?= App\Lib\Util::e($e['category']) ?></span></td>
      <td><?= App\Lib\Util::e($e['payer_name']) ?></td>
      <td><?= App\Lib\Util::money($e['amount']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<div class="row">
  <?php if (($page??1)>1): ?><a class="btn secondary" href="?page=<?= (int)($page-1) ?>&q=<?= urlencode($q ?? '') ?>">Prev</a><?php endif; ?>
  <a class="btn secondary" href="?page=<?= (int)(($page??1)+1) ?>&q=<?= urlencode($q ?? '') ?>">Next</a>
  <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>">Back</a>
  <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>/ledger">Ledger</a>
</div>
