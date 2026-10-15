<?php
declare(strict_types=1);

// Comprueba que cada llamada a t() con texto literal tiene su traducción en lang/en.php.
// Uso: php scripts/check_translations.php [--verbose]   (termina con código 1 si falta alguna)
define('BASE_PATH', dirname(__DIR__));
$en = require BASE_PATH . '/lang/en.php';

$regex = <<<'RE'
/\bt\('((?:[^'\\]|\\.)*)'/
RE;

$used = [];
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(BASE_PATH, FilesystemIterator::SKIP_DOTS)
);
foreach ($files as $file) {
    $path = str_replace('\\', '/', $file->getPathname());
    if (!str_ends_with($path, '.php') || str_contains($path, '/lang/') || str_contains($path, '/.git/')) {
        continue;
    }
    if (preg_match_all($regex, (string) file_get_contents($path), $m)) {
        foreach ($m[1] as $key) {
            $used[stripcslashes($key)] = $path;
        }
    }
}

$missing = array_diff_key($used, $en);
$unused = array_diff_key($en, $used);

// Claves usadas de forma indirecta (t($variable)) aparecen como «sin uso directo»: solo se listan con --verbose
if (in_array('--verbose', $argv, true)) {
    foreach ($unused as $key => $_) {
        fwrite(STDERR, "AVISO sin uso directo en lang/en.php: $key\n");
    }
}
foreach ($missing as $key => $path) {
    fwrite(STDERR, "FALTA traducción: «{$key}» (en " . substr($path, strlen(BASE_PATH) + 1) . ")\n");
}
echo count($used) . " textos revisados, " . count($missing) . " sin traducir.\n";
exit($missing ? 1 : 0);
