<?php $g=$group; ?>
<div class="row" style="justify-content:space-between;align-items:center;">
  <h2 style="margin:0;">Ledger — <?= App\Lib\Util::e($g['name']) ?></h2>
  <div class="row">
    <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>/expenses">Expenses</a>
    <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>/settlements">Settlements</a>
    <a class="btn" href="/groups/<?= (int)$g['id'] ?>/ledger/simplify">Simplify Debts</a>
  </div>
</div>

<div class="grid cols-2">
  <div class="card">
    <h3>Balances</h3>
    <ul>
      <?php foreach ($members as $m): $b=$balances[$m['id']] ?? 0; ?>
        <li><?= App\Lib\Util::e($m['name']) ?> (#<?= (int)$m['id'] ?>): <strong><?= $b>0?'+':'' ?><?= App\Lib\Util::money($b) ?></strong></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="card">
    <h3>Suggested Transfers</h3>
    <ul>
      <?php foreach ($transfers as $t): ?>
        <li>#<?= (int)$t['from'] ?> → #<?= (int)$t['to'] ?>: <?= App\Lib\Util::money($t['amount']) ?></li>
      <?php endforeach; if (empty($transfers)): ?>
        <li class="muted">All settled up 🎉</li>
      <?php endif; ?>
    </ul>
  </div>
</div>
