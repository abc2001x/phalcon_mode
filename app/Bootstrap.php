<?php

use Phalcon\Loader;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Application as BaseApplication;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Config;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Cache\Frontend\Data as FrontendData;
use Phalcon\Cache\Backend\Memcache as BackendMemcache;
use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;

$getDi = null;

class Bootstrap extends BaseApplication
{
    private static $app = null;

    /**
     * 系统配置信息
     * @var array $configs
     */
    protected $configs;


    /**
     * 错误码对应表
     * @var array $array
     */
    protected $errorCodes;

    /**
     * Register the services here to make them general or register in the ModuleDefinition to make them module-specific
     */
    
    public static function getApp(){
        return self::$app;
    }

    public static function get_current_ctrl()
    {
        $dispatcher = self::getApp()->getDi()->getDispatcher();
        $actionName = $dispatcher->getActionName();
        $controllerName = $dispatcher->getControllerName().'Controller';
        $namespace = $dispatcher->getDefaultNamespace();

        $className = $namespace.'\\'.ucwords($controllerName);
        return $className;
    }

    public function getConfig(){

        if (!$this->configs) {

            $this->configs = yaml_parse_file(dirname(__DIR__).'/config/config.yaml');
        }
        // var_dump($this->configs);
        return $this->configs;
    }

    protected function initRouters(){

        $di = $this->getDi();
        $router = new Library\DefaultRouter();
        $router->setDi($di);
        
        foreach ($this->getModules() as $module) {
            // print_r($module);
            $routesClassName = str_replace('Module', 'Routes', $module['className']);

            if (class_exists($routesClassName)) {
                // echo $routesClassName;    
                $routesClass = new $routesClassName();
                $router = $routesClass->init($router);
            }
        }
    
        $di->set('router', $router);

    }


    protected function initCache(){
        $config = $this->getConfig()['apps_data'];
        $di = $this->getDi();
        // 设置模型缓存服务
        $di->set('modelsCache', function ()use($config) {

            // 默认缓存时间为一天
            $frontCache = new FrontendData(
                array(
                    "lifetime" => 86400
                )
            );

            // Memcached连接配置 这里使用的是Memcache适配器
            $cache = new BackFile(
                $frontCache,
                array(
                    "cacheDir" => dirname(__DIR__).$config['tmp_path']."/back/"
                )
            );

            return $cache;
        });
    }

    protected function initSession(){
        $di = $this->getDi();
        $di->set('session',function(){

            $session = new \Phalcon\Session\Adapter\Files();
            $session->start();
            return $session;
        },true);
    }

    protected function initConfig() {
        $di = $this->getDI();
        $di->set('configs', function() {
            $config  = yaml_parse_file(dirname(__DIR__).'/config/config.yaml');
            $configs = new Config($config);
            $configs->root_path=dirname(__DIR__);
            return $configs;
        },true);
    }


    protected function initView(){
        
        $di = $this->getDi();

        $di->set('view', function () {
            $view = new \Library\BaseView();
            // $view = new \Phalcon\Mvc\View();
            
            //绝对路径的使用方法
            $view->setMainView(__DIR__.'/views/main');
            $view->setLayoutsDir(__DIR__.'/views/layouts/');
            $view->setLayout('main');
            $view->setPartialsDir(__DIR__.'/views/partials/');
            
            //结合base path 使用相对路径
            // $view->setBasePath(__DIR__);
            // $view->setMainView('/views/main');
            // $view->setLayoutsDir('/views/layouts/');
            // $view->setLayout('main');
            // $view->setPartialsDir('/views/partials/');


            $view->registerEngines(array(
                ".volt" => function ($view, $di) {
                    $config = $this->getConfig()['apps_data'];
                    // $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);
                    $volt = new \Library\BaseVolt($view, $di);
                    $volt->setOptions(['compiledPath' =>   dirname(__DIR__).$config['tmp_path'].'/volt/']);

                    return $volt;
                }
            ));

            return $view;
        });

    }

    protected function initApplication()
    {
        $configs = $this->getConfig();
        $app_path = dirname(__DIR__);
        define('ROOT', $app_path);
        // init namespace
        $loader = new Loader();
        $packages = $this->configs['namespaces'];
        foreach ($packages as $name => &$path) {
            $path =$app_path.$path;
        }
        unset($path);
        //init modules
        $modules = $this->getConfig()['apps_modules'];

        $moduleNameSpaces = [];
        foreach ($modules as $name => &$params) {
            $params['path'] = $app_path.$params['path'] ;
            $moduleNameSpaces[ucfirst($name)] = dirname($params['path']);
        }
        unset($params);
        
        $packages = array_merge($moduleNameSpaces,$packages);
        // print_r($packages);
        $loader->registerNamespaces($packages)->register();;
        
        //Register the installed modules
        $this->registerModules($modules);
    }

    protected function initDb(){
        $di = $this->getDi();
        
        // Setup the database service
        $di->set('db', function () {
            $config = $this->getConfig()['database'];

            $eventsManager = new EventsManager();

            $logger = new FileLogger(ROOT.$this->getConfig()['apps_data']['tmp_path'].'/'.date('Y-m-d').'.sql.log');

            // Listen all the database events
            $eventsManager->attach('db', function ($event, $connection) use ($logger) {
               
                if ($event->getType() == 'beforeQuery') {

                    $logger->info($connection->getSQLStatement());
                }
            });

            $db = new \Phalcon\Db\Adapter\Pdo\Mysql($config);
            // $db->query('set names utf-8');
            $db->setEventsManager($eventsManager);

            return $db;
        });

    }
    private function initEventManager()
    {
        $di = $this->getDi();

        $eventsManager = new \Phalcon\Events\Manager();
        $dispatcher = new \Phalcon\Mvc\Dispatcher();
        // $dispatcher = $di->get('dispatcher');

        $eventsManager->attach("dispatch", new \Plugins\CheckPoint);

        $dispatcher->setEventsManager($eventsManager);
        $di->set('dispatcher', $dispatcher);
    }


    private function initCookie() {
        $di = $this->getDI();
        $di->set('cookies', function() {
            $cookies = new \Phalcon\Http\Response\Cookies();
            $cookies->useEncryption(false);
            return $cookies;
        });
    }

    private function initTransactions(){
        $di = $this->getDI();
        $di->setShared('transactions', function () {
            return new TransactionManager();
        });
    }

    public function initAll(){
        self::$app = $this;
        $this->initApplication();
        
        $this->initEventManager();

        $this->initSession();
        
        $this->initView();

        $this->initRouters();

        $this->initDb();
        $this->initCache();

        $this->initConfig();

        $this->initCookie();
        $this->initTransactions();
    }

    public function run()
    {
        $this->initAll();
        try {

            //todo 按模块 判断 ,错误时输出json或者html页面
            $response = $this->handle();
            echo $response->getContent();

        } catch (\Exception $e) {    

            if ($e instanceof \Library\AjaxException) {
                \Plugins\Common::echoError($e->getCode(), $e->getMessage());
            } else {
                echo get_class($e), ": ", $e->getMessage(), "\n";
                echo " File=", $e->getFile(), "\n";
                echo " Line=", $e->getLine(), "\n";
                echo $e->getTraceAsString();
            }

        }
        
    }

}
