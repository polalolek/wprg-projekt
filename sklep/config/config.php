<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL',  '');
define('SITE_NAME', 'SklepOnline');
define('SITE_DESC', 'Twój sklep internetowy');

define('CURRENCY',       'PLN');
define('CURRENCY_SYMBOL','zł');

define('ITEMS_PER_PAGE', 12);

define('SESSION_NAME',     'sklep_sess');
define('SESSION_LIFETIME', 86400); // 24h

define('UPLOAD_DIR',      BASE_PATH . '/public/images/produkty/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB
define('UPLOAD_ALLOWED',  ['image/jpeg', 'image/png', 'image/webp']);

ini_set('session.name',            SESSION_NAME);
ini_set('session.gc_maxlifetime',  (string)SESSION_LIFETIME);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('Europe/Warsaw');

// Autoloader
spl_autoload_register(function (string $class): void {
    $dirs = [
        BASE_PATH . '/config/',
        BASE_PATH . '/src/',
        BASE_PATH . '/src/Models/',
        BASE_PATH . '/src/Controllers/',
        BASE_PATH . '/src/Middleware/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
