<?php

define('LARAVEL_START', microtime(true));

$helperPath = __DIR__.'/../vendor/october/rain/src/Support/helpers.php';

if (!file_exists($helperPath)) {
    echo 'Missing vendor files, try running "composer install" or use the Wizard installer.'.PHP_EOL;
    exit(1);
}

require $helperPath;

require __DIR__.'/../vendor/autoload.php';

$compiledPath = __DIR__.'/../storage/framework/compiled.php';

if (file_exists($compiledPath)) {
    require $compiledPath;
}
