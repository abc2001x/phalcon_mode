<?php
//加载公共类命名空间,加载后的用法如下:
use phalcon\Loader;
$configs = yaml_parse_file(dirname(__DIR__).'/config/config.yaml');

$loader = new Loader();
$packages = $configs['namespaces'];
foreach ($packages as $name => &$path) {
    $path =dirname(__DIR__).$path;
}
unset($path);

$loader->registerNamespaces($packages)->register();

use Library\Test as LTest;
$lt = new LTest();
$lt->test();

use Plugins\Test as PTest;
$pt = new PTest();
$pt->test();

use Model\Test as MTest;
$mt = new MTest();
$mt->test();