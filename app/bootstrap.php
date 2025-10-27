<?php
declare(strict_types=1);

// Autoload simple PSR-4 for App\ namespace
spl_autoload_register(function($class){
    if (str_starts_with($class, 'App\\')) {
        $path = __DIR__ . '/' . str_replace('App\\', '', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (file_exists($path)) require $path;
    }
});

// Start session early
App\Lib\Auth::startSession();
