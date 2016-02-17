<?php
namespace Model\Admin;

class UiMenus extends \Phalcon\Mvc\Model
{
	public $id ;
	public $name ;
	public $uri;
    public $pid;
    public $permission_id;
    public $creator_id;
    public $ctime;
    
    protected static $_cache = array();

    /**
     * Implement a method that returns a string key based
     * on the query parameters
     */
    protected static function _createKey($parameters)
    {
        $uniqueKey = array();

        if (!is_array($parameters)) {
            $uniqueKey[] = $parameters;
        }
        else{
            foreach ($parameters as $key => $value) {
                if (is_scalar($value)) {
                    $uniqueKey[] = $key . ':' . $value;
                } else {
                    if (is_array($value)) {
                        $uniqueKey[] = $key . ':[' . self::_createKey($value) .']';
                    }
                }
            }
        }
        

        return join(',', $uniqueKey);
    }

    public static function find($parameters = null)
    {
        // Create an unique key based on the parameters

        if (!is_array($parameters) || !array_key_exists('cache', $parameters)) {
            if (is_array($parameters)) {
                $parameters['cache'] = array(
                    "key"      => __CLASS__.self::_createKey($parameters),
                    "lifetime" => 3600
                );    
            }
            else{
                $parameters = [$parameters,'cache'=>["key"=> __CLASS__.self::_createKey($parameters),"lifetime" => 3600]];
            }
            
        }

        return parent::find($parameters);
    }

    public static function findFirst($parameters = null)
    {
        if (!is_array($parameters) || !array_key_exists('cache', $parameters)) {
            if (is_array($parameters)) {
                $parameters['cache'] = array(
                    "key"      => self::_createKey($parameters),
                    "lifetime" => 3600
                );    
            }
            else{
                $parameters = [$parameters,'cache'=>["key"=> self::_createKey($parameters),"lifetime" => 3600]];
            }
            
        }

        return parent::findFirst($parameters);
    }

    public function getSource()
    {
        return "admin_ui_menus";
    }

	public function initialize()
    {
        // $this->belongsTo('robots_id', '\Model\AdminRole', 'id');
        // $this->hasOne('role_id','AdminRole','id');
        $this->belongsTo('pid','\\Model\\Admin\\UiMenus','id',array("alias"=>'parent'));
        $this->hasMany('id','\\Model\\Admin\\UiMenus','pid',array("alias"=>'childs'));
        $this->hasOne('permission_id','\\Model\\Admin\\Permission','id',array("alias"=>'permission'));
        
    }

    public function onConstruct()
    {
        // echo "每一个实例在创建的时候单独进行初始化".PHP_EOL;
    }

    public function getTopParentMenus(){

        if ($this->pid==0) {
            return $this;
        }
        else{
            // var_dump($this);
            return $this->parent->getTopParentMenus();
        }
    }

    

}
