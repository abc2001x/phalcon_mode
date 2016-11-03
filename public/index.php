<?php
(new \Phalcon\Debug())->listen();

use Phalcon\DI\FactoryDefault;

include dirname(__DIR__).'/app/Bootstrap.php';

$application = new Bootstrap(new FactoryDefault());

$application->run();

// xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
