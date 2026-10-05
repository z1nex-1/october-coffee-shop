<?php

require __DIR__ . '/../bootstrap/autoload.php';

$loader = new October\Rain\Support\ClassLoader(
    new October\Rain\Filesystem\Filesystem,
    __DIR__ . '/../',
    __DIR__ . '/../storage/framework/classes.php'
);

$loader->register();
$loader->addDirectories([
    'modules',
    'plugins'
]);

$generatorPatchPath = __DIR__ . '/resources/patches/php-generator-7.php';
$generatorSourcePath = __DIR__ . '/../vendor/phpunit/phpunit-mock-objects/src/Generator.php';

if (file_exists($generatorSourcePath)) {
    file_put_contents($generatorSourcePath, file_get_contents($generatorPatchPath));
}
