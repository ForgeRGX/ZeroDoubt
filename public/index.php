<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/core/helpers.php';

require BASE_PATH . '/core/autoload.php';

date_default_timezone_set(env('TZ', 'Europe/Madrid'));
error_reporting(E_ALL);
ini_set('display_errors', config('debug') ? '1' : '0');
ini_set('log_errors', '1');

set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    if (config('debug')) {
        echo '<pre>' . e((string) $e) . '</pre>';
        return;
    }
    View::render('errors/500', [
        'title'   => 'Error',
        'dbError' => $e instanceof PDOException,
    ]);
});

ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Idioma: los visitantes sin sesión pueden elegirlo con ?lang=es|en; los usuarios lo guardan en Configuración
if (!Auth::check() && isset($_GET['lang']) && is_string($_GET['lang']) && isset(I18n::LANGS[$_GET['lang']])) {
    $_SESSION['lang'] = $_GET['lang'];
}
I18n::init();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    exit('Método no permitido');
}

if ($method === 'POST' && !csrf_check($_POST['_csrf'] ?? null)) {
    http_response_code(419);
    View::render('errors/419', ['title' => 'Sesión caducada']);
    exit;
}

// Solo /login y /register son públicas; el resto exige sesión iniciada
$publicPaths = ['/login', '/register'];
$path        = current_path();
if (!Auth::check() && !in_array($path, $publicPaths, true)) {
    redirect('/login');
}
if (Auth::check() && in_array($path, $publicPaths, true)) {
    redirect('/');
}

// Movimientos recurrentes: crea los que hayan vencido (como mucho una vez cada hora por sesión)
if (Auth::check() && $method === 'GET' && ($_SESSION['recurring_checked'] ?? 0) < time() - 3600) {
    Recurring::generateDue(Auth::id());
    $_SESSION['recurring_checked'] = time();
}

$router = new Router();
foreach (require BASE_PATH . '/config/routes.php' as [$m, $routePath, $handler]) {
    $router->add($m, $routePath, $handler);
}
$router->dispatch($method, $_SERVER['REQUEST_URI'] ?? '/');
