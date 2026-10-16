<?php

declare(strict_types=1);

use Framework\Config\EnvLoader;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$envLoader = new EnvLoader(dirname(__DIR__));
$envLoader->load();
