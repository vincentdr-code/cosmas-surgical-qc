<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists(\ = __DIR__.'/../storage/framework/maintenance.php')) {
    require \;
}

require __DIR__.'/../vendor/autoload.php';

\ = require_once __DIR__.'/../bootstrap/app.php';

\ = \->make(Kernel::class);

\ = \->handle(
    \ = Request::capture()
)->send();

\->terminate(\, \);
