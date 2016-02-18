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

}