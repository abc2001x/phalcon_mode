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
        $this->view->b = '404';
    }
    
    public function show503Action() {
        // $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_LAYOUT);
        $this->view->b = '503';
    }

    public function noauthAction() {

    }

    public function indexAction() {

        $this->session->set("user-name", "Michael");
        $this->view->a= 912;
        $this->view->b= 123;
        // throw new \Library\Exception("没有权限", 403);
        return true;
        // return [1,2,3,4,5,6,7,7,8,9];
        
        // echo 'abc';
        // throw new Exception("Error Processing Request", 1);
        
        // $this->view->pick("main");
        // echo "string";
        // echo $this->view->getMainView();
        // return true;
    }

    public function testAction(){
        // $this->view->disable();
        echo 'abc';
        return true;
    }

}