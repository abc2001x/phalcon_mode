<?php
namespace Library;

class BaseVolt extends \Phalcon\Mvc\View\Engine\Volt {
    public function __construct($a,$b){
        // $this->log = new \Phalcon\Logger\Adapter\File(getcwd().'/view.log');
        parent::__construct($a,$b);
    }

    public function render($templatePath,$params, $mustClean = false){

        // $this->log->info("volt render....templatePath:$templatePath\n");
        
        parent::render($templatePath,$params, $mustClean);
        
    }

    public function initCompiler()
    {
        $compiler = $this->getCompiler();

        $compiler->addFunction('helper', function () {
            return '$this->helper';
        });
        $compiler->addFunction('translate', function ($resolvedArgs) {
            return '$this->helper->translate(\'.$resolvedArgs.\')';
        });
        $compiler->addFunction('langUrl', function ($resolvedArgs) {
            return '$this->helper->langUrl(' . $resolvedArgs . ')';
        });
        $compiler->addFunction('image', function ($resolvedArgs) {
            return '(new \Image\Storage(' . $resolvedArgs . '))';
        });
        $compiler->addFunction('widget', function ($resolvedArgs) {
            return '(new \Application\Widget\Proxy(' . $resolvedArgs . '))';
        });

        $compiler->addFunction('substr', 'substr');

    }
}