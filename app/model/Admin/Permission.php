<?php
namespace Model\Admin;

class Permission extends \Phalcon\Mvc\Model
{
	public $id ;
    public $resource;
	public $action;
    public $level;

    public function getSource()
    {
        return "admin_permission";
    }


	public function initialize()
    {
        // $this->belongsTo('robots_id', '\Model\AdminRole', 'id');
        $this->hasOne('id','\\Model\\Admin\\UiMenus','permission_id');
        // $this->hasOne('role_id','\Model\AdminRole','id',array("foreignKey" => true,"alias"=>'role'));
        
    }

    public function onConstruct()
    {
        // echo "每一个实例在创建的时候单独进行初始化".PHP_EOL;
    }

    public static function getAllPermissionString(){
        $permissions = self::find();
        $result = [];
        foreach ($permissions as $v) {
            $result[]=$v->resource.'.'.$v->action;
        }
        return $result;
    }

}
