<?php
/**
 * Date: 15/10/23
 * Time: 21:08
 */

namespace Admin\Controllers;

use Library\AdminAuthController;

class IndexController extends \Library\BaseController {

    public function initialize()
    {
        // $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);

    }

    public static $no_auth_array = ['test'];


    public function show404Action() {

    }

    public function noauthAction() {

    }

    public function indexAction() {
        
        $this->view->a= 123;
        // $this->view->pick("main");
        // echo "string";
        // echo $this->view->getMainView();
        return true;
    }

    public function testAction(){
        
    }

}