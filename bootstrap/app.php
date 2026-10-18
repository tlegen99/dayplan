<?php

declare(strict_types=1);

use Framework\Core\Kernel;

$projectDir = dirname(__DIR__);
require $projectDir . '/vendor/autoload.php';

$kernel = new Kernel($projectDir);

return $kernel;
