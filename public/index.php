<?php

declare(strict_types=1);

use Framework\Config\EnvLoader;
use Framework\Database\Connection;
use Framework\Database\DatabaseConfig;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$envLoader = new EnvLoader(dirname(__DIR__));
$envLoader->load();

$dbConfig = DatabaseConfig::fromEnv(getenv());
$dbConnection = new Connection($dbConfig);
