<?php

define('APP_PATH', realpath(__DIR__ . DIRECTORY_SEPARATOR .'..'.DIRECTORY_SEPARATOR));

use Phalcon\DI\FactoryDefault;
// $a = yaml_parse_file(dirname(realpath('.')).'/config/config.yaml');
include dirname(__DIR__).'/app/Bootstrap.php';


$application = new Bootstrap(new FactoryDefault());

$application->run();
