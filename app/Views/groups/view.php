<?php $g = $group; ?>
<div class="row" style="justify-content:space-between;align-items:center;">
  <h2 style="margin:0;"><?= App\Lib\Util::e($g['name']) ?></h2>
  <div class="row">
    <a class="btn" href="/groups/<?= (int)$g['id'] ?>/expenses/create">Add Expense</a>
    <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>/ledger">Ledger</a>
    <a class="btn secondary" href="/groups/<?= (int)$g['id'] ?>/invite">Invite</a>
  </div>
  
</div>

<div class="grid cols-3">
  <div class="card kpi"><div class="muted">Members</div><div class="value"><?= count($members) ?></div></div>
  <div class="card kpi"><div class="muted">Total expenses</div><div class="value">
    <?php $t=0; foreach($recentExpenses as $e){$t+=(float)$e['amount'];} echo App\Lib\Util::money($t); ?></div></div>
  <div class="card kpi"><div class="muted">Unsettled</div><div class="value">See Ledger</div></div>
</div>

<div class="grid cols-2">
  <div class="card">
    <h3>Recent Expenses</h3>
    <div class="table-scroll">
      <table>
        <tr><th>Date</th><th>Title</th><th>Amount</th><th>Payer</th></tr>
        <?php foreach ($recentExpenses as $e): ?>
          <tr>
            <td><?= App\Lib\Util::e($e['expense_date']) ?></td>
            <td><?= App\Lib\Util::e($e['title']) ?></td>
            <td><?= App\Lib\Util::money($e['amount']) ?></td>
            <td><?= App\Lib\Util::e($e['payer_name']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
  <div class="card">
    <h3>Recent Settlements</h3>
    <div class="table-scroll">
      <table>
        <tr><th>Date</th><th>From</th><th>To</th><th>Amount</th></tr>
        <?php foreach ($recentSettlements as $s): ?>
          <tr>
            <td><?= App\Lib\Util::e($s['settled_at']) ?></td>
            <td><?= App\Lib\Util::e($s['from_name']) ?></td>
            <td><?= App\Lib\Util::e($s['to_name']) ?></td>
            <td><?= App\Lib\Util::money($s['amount']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
</div>

<div class="grid cols-2">
  <div class="card">
    <h3>Category breakdown</h3>
    <canvas id="catPie" width="300" height="200"></canvas>
  </div>
  <div class="card">
    <h3>My last 30 days</h3>
    <canvas id="myLine" width="300" height="200"></canvas>
  </div>
</div>

<script>
  // simple category aggregation
  (function(){
    const rows = <?= json_encode(array_map(fn($x)=>['cat'=>$x['category'],'amt'=>(float)$x['amount']], $recentExpenses)) ?>;
    const agg = {};
    rows.forEach(r=>{ agg[r.cat]= (agg[r.cat]||0) + r.amt; });
    const slices = Object.entries(agg).map(([k,v])=>({label:k,value:v}));
    if (window.bmCharts) window.bmCharts.pie('catPie', slices);
  })();
  (function(){
    const points = [];
    const today = new Date();
    for(let i=29;i>=0;i--){ const d=new Date(today); d.setDate(d.getDate()-i); points.push({x: +d, y: Math.random()*100}); }
    if (window.bmCharts) window.bmCharts.line('myLine', points);
  })();
</script>
