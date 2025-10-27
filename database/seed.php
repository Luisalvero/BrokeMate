<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php'; // bootstrap autoload and DB

use App\Lib\DB;

$pdo = DB::conn();

// migrate
$sql = file_get_contents(__DIR__ . '/migrations.sql');
$pdo->exec($sql);

// clear
$pdo->exec('DELETE FROM notifications; DELETE FROM activity_log; DELETE FROM settlements; DELETE FROM expense_allocations; DELETE FROM expense_participants; DELETE FROM expenses; DELETE FROM group_members; DELETE FROM public_links; DELETE FROM groups; DELETE FROM users;');

$now = date('Y-m-d H:i:s');

// users
$users = [
  ['Owner','owner@example.com','Owner123!'],
  ['Alice','alice@example.com','Alice123!'],
  ['Bob','bob@example.com','Bob123!'],
];
$insU = $pdo->prepare('INSERT INTO users(name,email,password_hash,is_temporary,currency,created_at) VALUES (?,?,?,?,?,?)');
$ids = [];
foreach ($users as $u) {
  $insU->execute([$u[0], strtolower($u[1]), password_hash($u[2], PASSWORD_DEFAULT), 0, 'USD', $now]);
  $ids[] = (int)$pdo->lastInsertId();
}
[$ownerId, $aliceId, $bobId] = $ids;

// group
$invite = 'BM-ROOM305';
$pdo->prepare('INSERT INTO groups(name,description,currency,owner_id,invite_code,is_public_readonly,created_at) VALUES (?,?,?,?,?,?,?)')
    ->execute(['Room 305','Sample seeded group','USD',$ownerId,$invite,0,$now]);
$groupId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO group_members(group_id,user_id,role,created_at) VALUES (?,?,?,?)")
    ->execute([$groupId,$ownerId,'owner',$now]);
$pdo->prepare("INSERT INTO group_members(group_id,user_id,role,created_at) VALUES (?,?,?,?)")
    ->execute([$groupId,$aliceId,'member',$now]);
$pdo->prepare("INSERT INTO group_members(group_id,user_id,role,created_at) VALUES (?,?,?,?)")
    ->execute([$groupId,$bobId,'member',$now]);

// expenses
// Rent $1200 paid by Owner, split even among 3
$pdo->prepare('INSERT INTO expenses(group_id,payer_id,title,amount,category,notes,expense_date,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)')
    ->execute([$groupId,$ownerId,'Rent',1200.00,'Rent','Monthly rent','2025-10-01',$now,$now]);
$rentId = (int)$pdo->lastInsertId();
foreach ([$ownerId,$aliceId,$bobId] as $uid) {
  $pdo->prepare('INSERT INTO expense_participants(expense_id,user_id) VALUES (?,?)')->execute([$rentId,$uid]);
}
// each 400
foreach ([[$ownerId,400],[$aliceId,400],[$bobId,400]] as [$uid,$owed]) {
  $pdo->prepare('INSERT INTO expense_allocations(expense_id,user_id,share_type,share_value,owed_amount) VALUES (?,?,?,?,?)')->execute([$rentId,$uid,'even',1,$owed]);
}

// Pizza $30 paid by Alice, exact: Alice 0, Owner 15, Bob 15
$pdo->prepare('INSERT INTO expenses(group_id,payer_id,title,amount,category,notes,expense_date,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)')
    ->execute([$groupId,$aliceId,'Pizza',30.00,'Food','Friday night','2025-10-10',$now,$now]);
$pizzaId = (int)$pdo->lastInsertId();
foreach ([$ownerId,$aliceId,$bobId] as $uid) { $pdo->prepare('INSERT INTO expense_participants(expense_id,user_id) VALUES (?,?)')->execute([$pizzaId,$uid]); }
foreach ([[$ownerId,15],[$aliceId,0],[$bobId,15]] as [$uid,$owed]) {
  $pdo->prepare('INSERT INTO expense_allocations(expense_id,user_id,share_type,share_value,owed_amount) VALUES (?,?,?,?,?)')->execute([$pizzaId,$uid,'exact',$owed,$owed]);
}

// Rideshare $21 paid by Bob, percentages Owner 50%, Alice 25%, Bob 25%
$pdo->prepare('INSERT INTO expenses(group_id,payer_id,title,amount,category,notes,expense_date,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)')
    ->execute([$groupId,$bobId,'Lyft',21.00,'Ride','Airport ride','2025-10-15',$now,$now]);
$rideId = (int)$pdo->lastInsertId();
foreach ([$ownerId,$aliceId,$bobId] as $uid) { $pdo->prepare('INSERT INTO expense_participants(expense_id,user_id) VALUES (?,?)')->execute([$rideId,$uid]); }
foreach ([[$ownerId,10.5],[$aliceId,5.25],[$bobId,5.25]] as [$uid,$owed]) {
  $pdo->prepare('INSERT INTO expense_allocations(expense_id,user_id,share_type,share_value,owed_amount) VALUES (?,?,?,?,?)')->execute([$rideId,$uid,'percent',0,$owed]);
}

// one settlement: Alice pays Owner $100
$pdo->prepare('INSERT INTO settlements(group_id,from_user_id,to_user_id,amount,method,note,settled_at,created_at) VALUES (?,?,?,?,?,?,?,?)')
    ->execute([$groupId,$aliceId,$ownerId,100.00,'venmo','Partial','2025-10-20',$now]);

echo "Seed done. Group invite code: BM-ROOM305\n";