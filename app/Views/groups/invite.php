<?php $g=$group; $link = $g['invite_code']; ?>
<div class="card" style="max-width:560px;margin:0 auto;">
  <h2>Invite members</h2>
  <p>Share this code to let friends join your group:</p>
  <div class="row">
    <strong><?= App\Lib\Util::e($link) ?></strong>
    <button class="btn secondary" data-copy="<?= App\Lib\Util::e($link) ?>">Copy</button>
  </div>
  <p class="muted">They can join at <code>/groups/join</code>.</p>
</div>
