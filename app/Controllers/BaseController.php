<?php

namespace App\Controllers;

use App\Lib\Util;

class BaseController
{
    protected function render(string $view, array $params = []): void
    {
        extract($params, EXTR_SKIP);
        $flash = Util::consumeFlash();
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        $layoutFile = __DIR__ . '/../Views/layout.php';
        include $layoutFile;
    }
}
