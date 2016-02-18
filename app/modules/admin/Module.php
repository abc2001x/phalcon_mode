<?php

namespace Admin;
use Phalcon\DiInterface;
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\ModuleDefinitionInterface;

class Module implements ModuleDefinitionInterface
{
    /**
     * Registers an autoloader related to the module
     *
     * @param DiInterface $di
     */
    public function registerAutoloaders(DiInterface $di = NULL)
    {


        $loader = new Loader();
        // echo __DIR__ . '/controllers/';

        $loader->registerNamespaces(array(
            __NAMESPACE__.'\Controllers' => __DIR__ . '/controllers/'
        ));

        $loader->register();
        $di->set('flash', function () {
            $flash = new \Phalcon\Flash\Direct(
                array(
                    'error'   => 'alert alert-danger',
                    'success' => 'alert alert-success',
                    'notice'  => 'alert alert-info',
                    'warning' => 'alert alert-warning'
                )
            );
            //关闭
            $flash->setImplicitFlush(false);
            return $flash;
        });
    }

    /**
     * Registers services related to the module
     *
     * @param DiInterface $di
     */
    public function registerServices(DiInterface $di)
    {
        /**
         * Read configuration
         */
        // $config = include APP_PATH . "/apps/frontend/config/config.php";

        $dispatcher = $di['dispatcher'];
        $dispatcher->setDefaultNamespace(__NAMESPACE__.'\Controllers');
        // $di['dispatcher'] = function() {
        //     $dispatcher = new Dispatcher();
        //     #不添加默认命名空间会导致路由中需要使用全类名

        //     return $dispatcher;
        // };


        /**
         * Setting up the view component
         */
        $view = $di['view'];

        //结合base path使用短路径设置
        // $view->setViewsDir('/modules/'.strtolower(__NAMESPACE__).'/views/');
        
        //不使用base path,直接设置全路径
        $view->setViewsDir(__DIR__.'/views/');
        
    }
}
