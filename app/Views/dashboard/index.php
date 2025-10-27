<h2>My Groups</h2>
<div class="grid cols-2">
  <?php foreach ($groups as $g): ?>
  <div class="card">
    <div class="row" style="justify-content:space-between;align-items:center;">
      <div>
        <h3 style="margin:0;"><a href="/groups/<?= (int)$g['id'] ?>"><?= App\Lib\Util::e($g['name']) ?></a></h3>
        <div class="muted">Role: <?= App\Lib\Util::e($g['role']) ?> · <?= App\Lib\Util::e($g['currency']) ?></div>
      </div>
      <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>">Open</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<h2>Recent Activity</h2>
<div class="card">
  <ul>
    <?php foreach ($activity as $a): ?>
      <li class="muted">[<?= App\Lib\Util::e($a['created_at']) ?>] <?= App\Lib\Util::e($a['action']) ?> <?= App\Lib\Util::e($a['entity']) ?>#<?= (int)$a['entity_id'] ?></li>
    <?php endforeach; ?>
  </ul>
</div>
