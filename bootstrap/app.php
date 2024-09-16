<?php

use App\Application;
use Http\Request;


// Запуск приложения
$request = Request::fromGlobals();

$application = new Application();

$application->handle($request);