<?php $g=$group; ?>
<div class="row" style="justify-content:space-between;align-items:center;">
  <h2 style="margin:0;">Settlements — <?= App\Lib\Util::e($g['name']) ?></h2>
  <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>/ledger">Ledger</a>
  
</div>

<div class="card" style="margin:10px 0;">
  <h3>Add Settlement</h3>
  <form method="post" action="/groups/<?= (int)$g['id'] ?>/settlements/create">
    <?= App\Lib\CSRF::field() ?>
    <div class="row">
      <input class="input" type="number" name="from_user_id" placeholder="From user ID" min="1" required>
      <input class="input" type="number" name="to_user_id" placeholder="To user ID" min="1" required>
      <input class="input" type="number" name="amount" step="0.01" placeholder="Amount" required>
      <select class="input" name="method"><option>cash</option><option>venmo</option><option>zelle</option><option>other</option></select>
      <input class="input" type="date" name="settled_at" value="<?= date('Y-m-d') ?>">
      <input class="input" name="note" placeholder="Note (optional)">
      <button class="btn">Record</button>
    </div>
  </form>
  <p class="muted">Tip: use user IDs from the ledger balances below.</p>
  <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>/ledger/simplify">Simplify debts</a>
  
</div>

<div class="grid cols-2">
  <div class="card">
    <h3>Recent</h3>
    <div class="table-scroll">
      <table>
        <tr><th>Date</th><th>From</th><th>To</th><th>Amount</th></tr>
        <?php foreach ($settlements as $s): ?>
          <tr>
            <td><?= App\Lib\Util::e($s['settled_at']) ?></td>
            <td><?= App\Lib\Util::e($s['from_name']) ?> (#<?= (int)$s['from_user_id'] ?>)</td>
            <td><?= App\Lib\Util::e($s['to_name']) ?> (#<?= (int)$s['to_user_id'] ?>)</td>
            <td><?= App\Lib\Util::money($s['amount']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
  <div class="card">
    <h3>Balances</h3>
    <ul>
      <?php foreach ($balances as $uid=>$b): ?>
        <li>#<?= (int)$uid ?> — <?= $b>0?'+':'' ?><?= App\Lib\Util::money($b) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
