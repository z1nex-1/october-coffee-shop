<?php

$app = new October\Rain\Foundation\Application(
    realpath(__DIR__.'/../')
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    October\Rain\Foundation\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    October\Rain\Foundation\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    October\Rain\Foundation\Exception\Handler::class
);

return $app;
