<?php

$app = require __DIR__.'/src/app.php';

$app['http_cache']->run();
//$app->run();