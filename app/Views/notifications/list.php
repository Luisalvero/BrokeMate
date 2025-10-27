<div class="card">
  <h2>Notifications</h2>
  <ul>
    <?php foreach ($notifications as $n): $p=json_decode($n['payload_json']??'{}',true) ?: []; ?>
      <li class="<?= $n['is_read']? 'muted':'' ?>">[<?= App\Lib\Util::e($n['created_at']) ?>] <?= App\Lib\Util::e($n['type']) ?> — <?= App\Lib\Util::e(json_encode($p)) ?></li>
    <?php endforeach; ?>
  </ul>
  <form method="post" action="/notifications/mark-read">
    <?= App\Lib\CSRF::field() ?>
    <button class="btn secondary">Mark all read</button>
  </form>
</div>
