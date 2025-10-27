<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Lib\Auth;
use App\Lib\CSRF;
use App\Lib\Util;

// Basic router
$ROUTES = [];
function route(string $method, string $pattern, callable $handler): void {
    global $ROUTES; $ROUTES[] = [$method, $pattern, $handler];
}
function dispatch(): void {
    global $ROUTES; $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); $verb = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    foreach ($ROUTES as [$m, $pat, $h]) {
        if ($m !== $verb) continue;
        $regex = '#^' . preg_replace('#\{([a-z_][a-z0-9_]*)\}#i', '(?P<$1>[^/]+)', $pat) . '$#i';
        if (preg_match($regex, $path, $mats)) {
            $args = [];
            foreach ($mats as $k=>$v) if (!is_int($k)) $args[] = ctype_digit($v) ? (int)$v : $v;
            $h(...$args);
            return;
        }
    }
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
}

// Routes
route('GET','/', function(){
    if (App\Lib\Auth::check()) { Util::redirect('/dashboard'); }
    $flash = Util::consumeFlash();
    $viewFile = __DIR__.'/../app/Views/home.php'; $layoutFile = __DIR__.'/../app/Views/layout.php'; include $layoutFile;
});

// Auth
route('GET','/login', fn()=> (new App\Controllers\AuthController())->showLogin());
route('POST','/login', fn()=> (new App\Controllers\AuthController())->login());
route('GET','/register', fn()=> (new App\Controllers\AuthController())->showRegister());
route('POST','/register', fn()=> (new App\Controllers\AuthController())->register());
route('POST','/guest', fn()=> (new App\Controllers\AuthController())->guest());
route('GET','/logout', fn()=> (new App\Controllers\AuthController())->logout());

// Dashboard
route('GET','/dashboard', fn()=> (new App\Controllers\DashboardController())->index());

// Groups
route('GET','/groups/create', fn()=> (new App\Controllers\GroupController())->createForm());
route('POST','/groups/create', fn()=> (new App\Controllers\GroupController())->create());
route('GET','/groups/join', fn()=> (new App\Controllers\GroupController())->joinForm());
route('POST','/groups/join', fn()=> (new App\Controllers\GroupController())->join());
route('GET','/groups/{id}', fn($id)=> (new App\Controllers\GroupController())->view((int)$id));
route('GET','/groups/{id}/invite', fn($id)=> (new App\Controllers\GroupController())->invite((int)$id));

// Expenses
route('GET','/groups/{id}/expenses', fn($id)=> (new App\Controllers\ExpenseController())->list((int)$id));
route('GET','/groups/{id}/expenses/create', fn($id)=> (new App\Controllers\ExpenseController())->createForm((int)$id));
route('POST','/groups/{id}/expenses/create', fn($id)=> (new App\Controllers\ExpenseController())->create((int)$id));

// Settlements
route('GET','/groups/{id}/settlements', fn($id)=> (new App\Controllers\SettlementController())->list((int)$id));
route('POST','/groups/{id}/settlements/create', fn($id)=> (new App\Controllers\SettlementController())->create((int)$id));

// Ledger
route('GET','/groups/{id}/ledger', fn($id)=> (new App\Controllers\LedgerController())->view((int)$id));
route('GET','/groups/{id}/ledger/simplify', fn($id)=> (new App\Controllers\SettlementController())->simplifyPreview((int)$id));
route('POST','/groups/{id}/ledger/simplify', fn($id)=> (new App\Controllers\SettlementController())->simplifyConfirm((int)$id));

// Notifications
route('GET','/notifications', fn()=> (new App\Controllers\NotificationController())->list());
route('POST','/notifications/mark-read', fn()=> (new App\Controllers\NotificationController())->markRead());

// Settings
route('GET','/settings/profile', fn()=> (new App\Controllers\ProfileController())->settings());
route('POST','/settings/profile', fn()=> (new App\Controllers\ProfileController())->update());
route('POST','/settings/password', fn()=> (new App\Controllers\ProfileController())->setPassword());

// Public
route('GET','/public/{token}', fn($t)=> (new App\Controllers\PublicController())->readOnly($t));

// Dispatch
dispatch();
