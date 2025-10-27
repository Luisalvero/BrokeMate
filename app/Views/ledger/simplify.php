<?php $g=$group; ?>
<div class="card">
  <h2>Simplify Debts — <?= App\Lib\Util::e($g['name']) ?></h2>
  <p>We propose the following transfers to settle up:</p>
  <ul>
    <?php foreach ($transfers as $t): ?>
      <li>#<?= (int)$t['from'] ?> → #<?= (int)$t['to'] ?>: <?= App\Lib\Util::money($t['amount']) ?></li>
    <?php endforeach; if (empty($transfers)): ?><li class="muted">No transfers needed.</li><?php endif; ?>
  </ul>
  <?php if (!empty($transfers)): ?>
  <form method="post" action="/groups/<?= (int)$g['id'] ?>/ledger/simplify">
    <?= App\Lib\CSRF::field() ?>
    <button class="btn success">Record these settlements</button>
    <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>/ledger">Cancel</a>
  </form>
  <?php else: ?>
  <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>/ledger">Back</a>
  <?php endif; ?>
</div>
