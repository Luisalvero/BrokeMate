<?php $g=$group; ?>
<div class="card">
  <h2><?= App\Lib\Util::e($g['name']) ?> — public read-only</h2>
  <p class="muted">Member initials and recent expenses only.</p>
  <p>Members: <?php foreach ($members as $m): ?><span class="badge" style="margin-right:4px;"><?= App\Lib\Util::e($m['initial']) ?></span><?php endforeach; ?></p>
  <div class="table-scroll">
    <table>
      <tr><th>Date</th><th>Title</th><th>Category</th><th>Amount</th></tr>
      <?php foreach ($expenses as $e): ?>
        <tr>
          <td><?= App\Lib\Util::e($e['expense_date']) ?></td>
          <td><?= App\Lib\Util::e($e['title']) ?></td>
          <td><?= App\Lib\Util::e($e['category']) ?></td>
          <td><?= App\Lib\Util::money($e['amount']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
