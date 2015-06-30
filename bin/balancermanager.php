<?php

if (PHP_SAPI !== 'cli') {
    echo 'Warning: Composer should be invoked via the CLI version of PHP, not the ' . PHP_SAPI . ' SAPI' . PHP_EOL;
}

require __DIR__ . '/../src/bootstrap.php';

use Marktjagd\LoadBalancerManager\Console\Application;

error_reporting(-1);

// run the command application
$application = new Application();
$application->run();
