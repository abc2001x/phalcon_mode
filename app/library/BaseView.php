<?php
namespace Library;

class BaseView extends \Phalcon\Mvc\View {
    public function __construct($opt=null){
        $this->renderCounter = 0;//大于零则表示为非action模板定位
        // $this->log = new \Phalcon\Logger\Adapter\File(getcwd().'/view.log');
        parent::__construct($opt);
    }

    protected function _engineRender($engines, $viewShortPath, $silence, $mustClean, \Phalcon\Cache\BackendInterface $cache = null) {      
        // $silence = false;
        // $this->log->info("engines render...".$this->renderCounter."...viewShortPath:$viewShortPath </br>\n");
        
        // $basePath = $this->getBasePath();
        //如果设置basePath,使用短路径查找. 
        //则目录路径 = view->basePath + $view->viewPath + $viewShortPath
        //因为viewPath是根据Controller/Action动态设置的,故此,根据renderCounter判断是否为action的匹配
        //非action定位,把ViewsDir设置为空

        if ($this->renderCounter>0) {
            $this->cleanViewsDir();
        }

        $this->renderCounter+=1;

        return parent::_engineRender($engines, $viewShortPath, $silence, $mustClean, $cache);
    }

    public function cleanViewsDir()
    {
        // echo $this->_viewsDir;
        // $basePath = $this->getBasePath();
        // if (!$basePath) {
        //     $this->setBasePath($this->_viewsDir);
        //     $this->_viewsDir = "";
        // }
        $this->_viewsDir = "";
    }
    // public function render($controllerName, $actionName, $params = null){

    //     $this->log->info("render....controllerName:$controllerName ,actionName:$actionName  \n");
        
    //     $a = parent::render($controllerName, $actionName, $params);
        
    //     return $a;

    // }

    // public function finish(){
    //     echo "finish...</br>";
    //     return parnt::finish();
    // }

    // public function partial($partialPath,$params = null){
    //     echo "partial...</br>";

    //     parent::partial($partialPath,$params);
    // }
}