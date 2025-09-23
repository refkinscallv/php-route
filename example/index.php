<?php

    use RFRoute\Route\Route;

    require '../vendor/autoload.php';

    require 'routes/index.php';

    Route::dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
