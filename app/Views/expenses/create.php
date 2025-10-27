<?php $g=$group; ?>
<div class="card">
  <h2>Add Expense — <?= App\Lib\Util::e($g['name']) ?></h2>
  <form method="post" action="/groups/<?= (int)$g['id'] ?>/expenses/create">
    <?= App\Lib\CSRF::field() ?>
    <div class="grid cols-2">
      <div class="field"><label>Title</label><input class="input" name="title" required></div>
      <div class="field"><label>Amount</label><input class="input" name="amount" type="number" step="0.01" required></div>
      <div class="field"><label>Payer</label>
        <select class="input" name="payer_id">
          <?php foreach (($members??[]) as $m): ?><option value="<?= (int)$m['id'] ?>"><?= App\Lib\Util::e($m['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Date</label><input class="input" name="expense_date" type="date" value="<?= date('Y-m-d') ?>" required></div>
      <div class="field"><label>Category</label>
        <select class="input" name="category">
          <?php foreach (['Food','Rent','Utilities','Ride','Trip','Other'] as $c): ?><option><?= $c ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Notes</label><input class="input" name="notes"></div>
    </div>
    <div class="field">
      <label>Participants</label>
      <div class="row">
        <?php foreach ($members as $m): ?>
          <label><input type="checkbox" name="participants[]" value="<?= (int)$m['id'] ?>" checked> <?= App\Lib\Util::e($m['name']) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field">
      <label>Split Method</label>
      <select class="input" name="split_method" id="split_method">
        <option value="even">Even</option>
        <option value="shares">By Shares</option>
        <option value="exact">Exact Amounts</option>
        <option value="percent">Percentages</option>
      </select>
    </div>
    <div id="split_fields" class="card" style="margin:10px 0;">
      <div class="muted">Additional fields will show here for the selected split.</div>
    </div>
    <button class="btn">Add Expense</button>
  </form>
</div>
<script>
  const members = <?= json_encode($members) ?>;
  const wrap = document.getElementById('split_fields');
  const method = document.getElementById('split_method');
  const render = () => {
    const m = method.value; let html='';
    if (m==='shares') {
      html = '<div class="grid cols-2">' + members.map(m=>`<div class=field><label>${m.name} shares</label><input class=input type=number name=shares[${m.id}] min=0 step=1 value=1></div>`).join('') + '</div>';
    } else if (m==='exact') {
      html = '<div class="grid cols-2">' + members.map(m=>`<div class=field><label>${m.name} amount</label><input class=input type=number name=exact[${m.id}] min=0 step=0.01></div>`).join('') + '</div>';
    } else if (m==='percent') {
      html = '<div class="grid cols-2">' + members.map(m=>`<div class=field><label>${m.name} percent</label><input class=input type=number name=percent[${m.id}] min=0 step=0.01></div>`).join('') + '</div>';
    } else { html = '<div class=muted>Even split among selected participants.</div>'; }
    wrap.innerHTML = html;
  };
  method.addEventListener('change', render); render();
</script>
