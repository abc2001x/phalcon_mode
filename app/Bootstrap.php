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
    
    public function createDir($dir){
        return is_dir($dir) or ($this->createDir(dirname($dir)) and @mkdir($dir, 0777));
    }

    public function getModule($moduleName){
        if (!array_key_exists($moduleName, $this->_modules)) {
            if (!$this->_defaultModule) {
                throw new \Phalcon\Application\Exception("Module '" . $moduleName . "' isn't registered in the application container");
                
            }
            
            return $this->_modules[$this->_defaultModule];

        }

        return $this->_modules[$moduleName];
    }

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
            $this->getDi()->set('configs',function(){
                $configs = require(dirname(__DIR__).'/config/config.php');
                return $configs;
            },true);
            
            $this->configs = $this->getDi()->get('configs');
        }
        
        return $this->configs;
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
        $loader->registerNamespaces($packages)->register();;
        
        //Register the installed modules
        $this->registerModules($modules);

        $di = $this->getDI();
        $di->setShared('application',$this);
        $eventsManager = new EventsManager();
        $eventsManager->attach(
            "application",
            new \Plugins\AutoResponse()
        );

        $this->setEventsManager($eventsManager);
    }

    protected function initDispatcher(){
        $di = $this->getDi();

        $di->setShared('dispatcher',function(){
            $eventsManager = new EventsManager();
            $eventsManager->attach(
                "dispatch",
                new \Plugins\AutoResponse()    
            );

            $mvcDispatcher =  new \Phalcon\Mvc\Dispatcher();
            $mvcDispatcher->setEventsManager($eventsManager);

            return $mvcDispatcher;
        });
    }

    protected function initSession(){
        $di = $this->getDi();
        $di->set('session',function(){
            $configs = $this->get('configs');
            if (array_key_exists('memcache', $configs)) {
                $session = new \Phalcon\Session\Adapter\Memcache([
                     'uniqueId'   => 'session_app',
                     'host'       => $configs['memcache']['host'],
                     'port'       => $configs['memcache']['port'],
                     'persistent' => true,
                     'lifetime'   => 3600,
                     'prefix'     => 'session_'
                 ]); 
            }else{
                $session = new \Phalcon\Session\Adapter\Files();    
            }
            
            $session->start();
            return $session;
        },true);
    }

    protected function initCache(){
        $di = $this->getDi();
        // 设置模型缓存服务
        $di->set('modelsCache', function (){
            $configs = $this->getDi()->get('configs');
            // 默认缓存时间为一天
            $frontCache = new FrontendData(
                array(
                    "lifetime" => 86400
                )
            );

            if (array_key_exists('memcache', $configs)) {
                return new Phalcon\Cache\Backend\Memcache($frontCache,[
                    'host'       => $configs['memcache']['host'],
                    'port'       => $configs['memcache']['port'],
                    'persistent' => true,
                    'lifetime'   => 3600,
                    'prefix'     => 'session_'
                    ]);
            }

            // Memcached连接配置 这里使用的是Memcache适配器
            return new BackFile(
                $frontCache,
                array(
                    "cacheDir" => dirname(__DIR__).$configs['apps_data']['cache']
                )
            );

        });
    }

    protected function initRouters(){

        $di = $this->getDi();
        $router = new Library\DefaultRouter();
        $router->setDi($di);
        
        foreach ($this->getModules() as $module) {
            $routesClassName = str_replace('Module', 'Routes', $module['className']);

            if (class_exists($routesClassName)) {
                // echo $routesClassName;    die();
                $routesClass = new $routesClassName();
                $router = $routesClass->init($router);
            }
        }
    
        $di->set('router', $router);

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

    protected function initDb(){
        $di = $this->getDi();
        
        // Setup the database service
        $di->set('db', function () {
            $configs = $this->get('configs')['database'];

            $eventsManager = new EventsManager();

            $logger = new FileLogger(ROOT.$this->getConfig()['apps_data']['logs'].'/'.date('Y-m-d').'.sql.log');

            // Listen all the database events
            $eventsManager->attach('db', function ($event, $connection) use ($logger) {
               
                if ($event->getType() == 'beforeQuery') {

                    $logger->info($connection->getSQLStatement());
                }
            });

            $db = new \Phalcon\Db\Adapter\Pdo\Mysql($configs);
            // $db->query('set names utf-8');
            $db->setEventsManager($eventsManager);

            return $db;
        });

    }

    private function initView()
    {
        $di = $this->getDi();
        $configs = $this->configs['apps_data'];
        $view = new \Phalcon\Mvc\View();

        define('MAIN_VIEW_PATH', '../../../views/');
        $view->setMainView(MAIN_VIEW_PATH . 'admin');//设置顶级布局文件
        $view->setLayoutsDir(MAIN_VIEW_PATH . '/layouts/');//设置二级布局目录
        $view->setLayout('main');//设置二级布局目录下的文件
        $view->setPartialsDir(MAIN_VIEW_PATH . '/partials/');//设置片段目录
        
        // Volt
        $volt = new \Library\BaseVolt($view, $di);
        $path = dirname(__DIR__).$configs['volt'];

        // $volt->setOptions(
        //     [
        //         "compiledPath" => function ($templatePath)use($path) {
        //             $t = str_replace(dirname(__DIR__), '', $templatePath);
        //             return $path . $t .".php";
        //         }
        //     ]
        // );
        $volt->setOptions([
            'compiledPath' => dirname(__DIR__).$configs['volt'],
            'compiledSeparator'=>'-',
            ]);
        $volt->initCompiler();

        $phtml = new \Phalcon\Mvc\View\Engine\Php($view, $di);
        $viewEngines = [
            ".volt"  => $volt,
            ".phtml" => $phtml,
        ];

        $view->registerEngines($viewEngines);

        $di->set('view', $view);
        $this->initViewCache();
        return $view;
    }

    public function initViewCache(){
        $di = $this->getDi();

        $di->set(
            "viewCache",
            function () {
                $configs = $this->get('configs');
                // Cache data for one day by default
                $frontCache = new Phalcon\Cache\Frontend\Output(
                    [
                        "lifetime" => 86400,
                    ]
                );

                // Memcached connection settings
                if (array_key_exists('memcache', $configs)) {
                    return new Phalcon\Cache\Backend\Memcache($frontCache,[
                        'host'       => $configs['memcache']['host'],
                        'port'       => $configs['memcache']['port'],
                        'persistent' => true,
                        'lifetime'   => 3600,
                        'prefix'     => 'view_cache_'
                    ]);
                }

                $dirPath = dirname(__DIR__).$configs['apps_data']['cache'];
                return new BackFile(
                    $frontCache,
                    [
                        "cacheDir" => $dirPath
                    ]
                );
            }
        );
    }

    private function initDirs(){
        $di = $this->getDI();
        $configs = $di->get('configs');
        if (!$configs['debug']) {
            return;
        }
        foreach ($configs['apps_data'] as $v) {
            $this->createDir(ROOT.$v);
        }
    }

    public function initAll(){
        $app_path = dirname(__DIR__);
        define('ROOT', $app_path);

        self::$app = $this;
        $this->setDefaultModule('admin');
        $this->initApplication();
        $this->initDirs();
        $this->initCache();
        $this->initSession();
        $this->initCookie();
        $this->initRouters();
        $this->initTransactions();
        $this->initDb();
        $this->initView();
        $this->initDispatcher();
        
    }

    public function run()
    {   
        $this->initAll();
        $configs = $this->getDi()->get('configs');

        if ($configs['debug']) {

            $response = $this->handle();
            echo $response->getContent();
            
            return;
        }
        
        try {   

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
