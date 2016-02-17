<?php
namespace Library;
use Phalcon\Mvc\Router;

class DefaultRouter extends Router
{

    const ML_PREFIX = 'ml__';

    public function __construct()
    {
        parent::__construct();

        $this->setDefaultController('index');
        $this->setDefaultAction('index');
		// $this->setDefaultModule("admin");

        $this->add('/:module/:controller/:action/:params', [
            'module' => 1,
            'controller' => 2,
            'action' => 3,
            'params' => 4
        ])->setName('default');
        $this->add('/:module/:controller', [
            'module' => 1,
            'controller' => 2,
            'action' => 'index',
        ])->setName('default_action');
        $this->add('/:module', [
            'module' => 1,
            'controller' => 'index',
            'action' => 'index',
        ])->setName('default_controller');

        //暂时使用后台管理当首页
        $this->add('/', [
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index',
        ])->setName('default_controller');

        $this->removeExtraSlashes(true);

    }

    public function addML($pattern, $paths = null, $name)
    {
    	$this->add($pattern, $paths)->setName(self::ML_PREFIX . $name);
    }

}

