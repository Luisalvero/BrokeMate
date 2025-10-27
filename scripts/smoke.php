<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Lib\DB;

$pdo = DB::conn();
$pdo->exec(file_get_contents(__DIR__ . '/../database/migrations.sql'));

echo "Users: ";
$count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo $count . "\n";

// Ledger sanity: sum of balances ~ 0 for seeded group if exists
$gid = $pdo->query('SELECT id FROM groups LIMIT 1')->fetchColumn();
if ($gid) {
  $balances = [];
  foreach ($pdo->query('SELECT u.id FROM users u JOIN group_members gm ON gm.user_id=u.id WHERE gm.group_id='.(int)$gid) as $r) { $balances[(int)$r['id']] = 0.0; }
  foreach ($pdo->query('SELECT payer_id, SUM(amount) t FROM expenses WHERE group_id='.(int)$gid.' GROUP BY payer_id') as $r) { $balances[(int)$r['payer_id']] += (float)$r['t']; }
  foreach ($pdo->query('SELECT ea.user_id, SUM(owed_amount) o FROM expense_allocations ea JOIN expenses e ON e.id=ea.expense_id WHERE e.group_id='.(int)$gid.' GROUP BY ea.user_id') as $r) { $balances[(int)$r['user_id']] -= (float)$r['o']; }
  foreach ($pdo->query('SELECT from_user_id, to_user_id, SUM(amount) a FROM settlements WHERE group_id='.(int)$gid.' GROUP BY from_user_id,to_user_id') as $r) { $balances[(int)$r['from_user_id']] -= (float)$r['a']; $balances[(int)$r['to_user_id']] += (float)$r['a']; }
  $sum = array_sum($balances);
  echo "Ledger sum: " . number_format($sum, 2) . " (should be ~0)\n";
}
echo "OK\n";