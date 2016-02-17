<?php
namespace Model\Admin;

class Role2Permission extends \Phalcon\Mvc\Model
{
	public $role_id ;
	public $permission_id ;

    public function getSource()
    {
        return "admin_role2permission";
    }


	public function initialize()
    {
        // echo "仅会被调用一次，目的是为应用中所有该模型的实例进行初始化".PHP_EOL;
    }

    public function onConstruct()
    {
        // echo "每一个实例在创建的时候单独进行初始化".PHP_EOL;
    }

}
