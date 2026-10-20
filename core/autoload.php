<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    foreach (['core', 'models', 'controllers'] as $dir) {
        $file = BASE_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
