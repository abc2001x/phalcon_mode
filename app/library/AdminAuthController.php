<?php
/**
 * Created by PhpStorm.
 * User: wangjiankun
 * Date: 15/9/21
 * Time: 11:37
 */

namespace Library;

use Phalcon\Mvc\Dispatcher;

class AdminAuthController extends BaseController{

    const USER_LOGIN_URL = '/admin/user/login';

    //不用对登录即可访问的
    public static $no_auth_array = ['findpwd','login','loginact','test', 'mlogin'];  

    public function afterExecuteRoute(Dispatcher $dispatcher){
        if ($this->isLogin()) {
            $user = self::get_current_user();
            $menus = \Service\Admin::getUserMenus($user);
            $this->view->menus = $menus;
            $this->view->user = $user;
        }
        else
        {
            $this->view->menus =['title'=>'无页面'];
        }
    }

    public function beforeExecuteRoute(Dispatcher $dispatcher) {


        $actionName = $dispatcher->getActionName();
        $controllerName  = $dispatcher->getControllerName() . 'Controller';
        $nameSpaceName = $dispatcher->getNamespaceName();

        $className = $nameSpaceName . '\\' . ucwords($controllerName) ;

        $no_auth_array = [];
        if ( class_exists($className) ) {
            $no_auth_array = array_merge($className::$no_auth_array, self::$no_auth_array);
        }

        if ( in_array($actionName, $no_auth_array) ) {
            return true;
        }


        if ($this->isLogin()) {
            //判断是否有权限操作此资源
            if (!$this->isAllowed($actionName)) {
                //echo '没有权限';
                $dispatcher->forward(array(
                    'controller'=>'index',
                    'action' => 'noauth'
                ));
                //die();
                return false;
            }
            return true;
        } else {

            if ( !$host = $this->request->getServerName() ) {
                $host = $this->request->getHttpHost();
            }
            $sourceUrl = $this->request->getScheme() . '://' . $host . $this->request->getURI();

            $url = $this->request->getScheme() . '://' . $host . self::USER_LOGIN_URL . '?ref=' . $sourceUrl;

            $this->redirect($url);
        }

    }

    /**
     * 验证当前用户是否已经登录
     * @return boolean|object
     */
    protected function isLogin() {

        if ($this->session->has('admin')) {
            $admin = $this->session->admin;
            return $admin;
        } else {
            return false;
        }
    }

    public static function get_current_user(){
        $session = \Bootstrap::getApp()->getDi()->getSession();
        if ($session->has('admin')) {
            $admin = $session->admin;
            return $admin;
        } else {
            return false;
        }


        return $admin;
    }

    public function isAllowed($action,$resource=null){
        $dispatcher = $this->getDI()->getDispatcher();
        if (!$resource) {
            $resource = $dispatcher->getControllerName();
        }
        $adminUser = self::get_current_user();

        $role = $adminUser->role;
        $acl = $role->getRoleAclWithCache();
        return $acl->isAllowed($role->name,strtolower($resource),strtolower($action));
        // var_dump($dispatcher->getControllerName());
        // die();
    }
}