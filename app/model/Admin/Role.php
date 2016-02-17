<?php
namespace Model\Admin;

use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Acl\Role as AclRole;
use Phalcon\Acl\Adapter\Memory as AclList;
use Phalcon\Acl\Resource;
class Role extends \Phalcon\Mvc\Model
{
	public $id ;
	public $name ;
    public $ctreator_id;

    public function getSource()
    {
        return "admin_role";
    }

	public function initialize()
    {
        $this->hasMany('id','\\Model\\Admin\\User','role_id',['alias'=>'user']);
        
        $this->hasOne('creator_id','\\Model\\Admin\\User','role_id',['alias'=>'creator']);

        $this->hasManyToMany(
            'id',
            '\\Model\\Admin\\Role2Permission',
            'role_id','permission_id',
            '\\Model\\Admin\\Permission',
            'id',
            ['alias'=>'permissions']
            );
        // echo "仅会被调用一次，目的是为应用中所有该模型的实例进行初始化".PHP_EOL;
    }
    public function beforeCreate()
    {
        // 事件函数,可在此预先处理一些插入动作
        $this->ctime = time();
        $creator = \Library\Utils::getAdminCreator();

        if($creator) {
            $this->creator_id = $creator->id;
        }
        
    }
    public function onConstruct()
    {
        // echo "每一个实例在创建的时候单独进行初始化".PHP_EOL;
    }

    public static function add($name){

        $role = new self();
        $role->name = $name;
        $role->save();

        return $role;
    }


    public function deleteRelation(){
        $phql = "DELETE FROM \\Model\\Admin\\Role2Permission WHERE role_id=:role_id:";
        $result = ['result'=>false,'message'=>''];

        try {
            $r = $this->getModelsManager()->executeQuery(
                $phql,
                array(
                    'role_id' => $this->id
                )
            );
        } catch (\Exception $e) {
            $result['result']=false;
            $result['message']=$e->getMessage();
        }
        
        $result['result']=$r->success();
        $result['message']=$r->getMessages();
        return $result;

    }

    public function setRolePermission($permissions_id){
        $result = ['result'=>false,'message'=>''];
        $formatValue = '(%s,%s)';
        $arrValues = [];
        if (!is_array($permissions_id)) {
            $result['message']='参数必须是数组';
            return $result;
        }
        if (!$this->id) {
            $result['message']='请使用完整的role实例调用此方法';
            return $result;
        }

        $this->deleteRelation($this->id);
        
        foreach ($permissions_id as $v) {
            $arrValues[] = sprintf($formatValue,$this->id,$v);
        }

        $strValues = implode(',',$arrValues);

        $phql = 'INSERT INTO admin_role2permission VALUES '.
                $strValues;
        // $phql = 'INSERT INTO admin_role2permission (role_id,permission_id) VALUES (1,2),(1,1)';
        // echo $phql;
        try {
            $r = $this->getReadConnection()->query($phql);  
            $result['result']=!!$r;  
        } catch (\Exception $e) {
            $result['result']=false;   
            $result['message'] = $e->getMessage(); 
        }
        
        return $result;
    }

    public function getMenusAndPermission($search=[]){
        $searchFields = [
                        'is_show'=>'menus.is_show',
                        'pid'=>'menus.pid',
                        ];
        $strWhere = '1';
        $arrWhere = [];

        $builder = $this->modelsManager->createBuilder()
            ->from(array('permission'=>'\\Model\\Admin\\Permission'))
            ->columns(['menus.*','permission.*'])
            ->leftjoin('\\Model\\Admin\\Role2Permission','permission.id = role2perm.permission_id','role2perm')
            ->leftjoin('\\Model\\Admin\\UiMenus','permission.id = menus.permission_id','menus')
            ->leftjoin('\\Model\\Admin\\Role','role2perm.role_id = role.id','role')
            
            
            ->where('role.id=:id:',['id'=>$this->id]);
        if ($search) {
            foreach ($search as $k => $v) {
                if (array_key_exists($k,$searchFields)) {
                    $strWhere .= ' AND '.$searchFields[$k]." = :{$k}: ";
                    $builder->andWhere($strWhere,[$k=>$v]);
                }
            }
        }
        $res = $builder->getQuery()->execute();
        // var_dump($res);
        // die();
        return $res;
    }

    public function getUrlMenus(){
        // ['title'=>xxxx,items=>['title'=>xxxx,'url'=>xxxxxxx]]
        $items =[];
        $permissions = $this->getMenusAndPermission();
        
        foreach ($permissions as $v) {
            $topParentMenus=$v->menus->getTopParentMenus();
            $menu = $v->menus;
            if (!array_key_exists($topParentMenus->id,$items)) {
                $items[$topParentMenus->id]=['title'=>$topParentMenus->name,'items'=>[]];    
            }
            //如果是不显示的菜单,显示查找父级菜单信息
            if ($menu->is_show=='0') {
                //有三级菜单的权限菜单,
                if ($menu->parent->id !=$topParentMenus->id) {
                    //挑第一个查询到结果当成他的url
                    if ($menu->parent->uri=='function:first') {
                        // echo "string";
                        if (!array_key_exists($menu->parent->id,$items[$topParentMenus->id]['items'])) {
                            $items[$topParentMenus->id]['items'][$menu->parent->id]=['title'=>$menu->parent->name,'uri'=>$menu->uri];
                        }
                    }
                    //不显示在菜单中
                    elseif ($menu->parent->uri=='function:none') {
                        
                    }
                
                }
                else{
                    //不显示在菜单中
                    if ($menu->uri=='function:none') {
                        
                    }
                    else{
                        $items[$topParentMenus->id]['items'][$menu->id]=['title'=>$menu->name,'uri'=>$menu->uri];
                    }
                }
                
                // =['title'=>$v->parent->name,'url'=>$v->url];

            }
            else
            {
                $items[$topParentMenus->id]['items'][$menu->id]=['title'=>$menu->name,'uri'=>$menu->uri];
            }
        }

        return $items;
    }


    public function getRoleAclWithCache($rebuild=0){
        $cache = $this->getDi()->getModelsCache();
        $key = 'acl.'.$this->name.'.'.$this->id;
        $acl = $cache->get($key);
        if (!$acl||$rebuild) {
            $acl = $this->constructPermission();
            $cache->save($key, $acl);
        }
        return $acl;
    }
    /**
     * 获取并建立某角色的权限文件
     * @author wugx
     * @version     1.0
     * @date        2015-11-04
     * @anotherdate 2015-11-04T16:23:57+0800
     * @return      [type]                   [description]
     */
    public function constructPermission(){

        $rolePermissions = $this->getMenusAndPermission();
        $allPermissions = \Model\Admin\Permission::getAllPermissionString();
        $userPermissions =[];
        foreach ($rolePermissions as $v) {
            if (!empty($v->permission->resource)) {
                $userPermissions[]=$v->permission->resource.'.'.$v->permission->action;
            }
        }
        // var_dump($userPermissions);
        //初始化所有权限
        $aclConfigs = self::getACLConfig();
        $resouceActionList=[];
        $resourceName='';
        $actionName ='';
        $item=[];
        $role = new AclRole($this->name);
        $acl = new AclList();
        $acl->addRole($role);
        $acl->setDefaultAction(\Phalcon\Acl::ALLOW);
        foreach ($allPermissions as $v) {
            $item = explode('.',$v);
            $resouceActionList[$item[0]][]=$item[1];
            if (array_key_exists($v, $aclConfigs)) {
                foreach ($aclConfigs[$v] as $vv) {
                    $item = explode('.',$vv);
                    $resouceActionList[$item[0]][]=$item[1];
                }
            }
        }

        foreach ($resouceActionList as $r => $a) {
            $acl->addResource(new Resource($r),$a);
        }

        //筛选允许和禁止的权限;
        foreach ($allPermissions as $v) {
            $item = explode('.',$v);

            if (in_array($v,$userPermissions)) {
                $method = 'allow';
            }
            else
            {
                $method ='deny';
            }
            // var_dump($this->name, $item[0],$item[1]);
            $acl->$method($this->name, $item[0],$item[1]);
            if (array_key_exists($v,$aclConfigs)) {
                foreach ($aclConfigs[$v] as $vv) {
                    $item=explode('.',$vv);
                    $acl->$method($this->name, $item[0],$item[1]);
                }
            }

        }

        // foreach ($resouceActionList as $r => $a) {
        //     $acl->addResource(new Resource($r),$a);
        // }
        // var_dump($acl);
        return $acl;
    }

    protected static function getACLConfig(){
        //绑定父子关系,有定义的才需要权限控制,其他为无权限控制项
        //若要控制开关,放入数据库中
        
        $configs = ['car.carlist'=>[
                                        'car.offline',//下架
                                        'car.online',//上架
                                        'car.position',//位置查看
                                        'car.add',//编辑
                                        'car.edit',//编辑
                                        'car.deleted',//删除
                                            
                                    ],
                    'order.pay'=>[
                                'order.confirm',//确认订单
                                'order.checkUserOrderExists',//检查有无订单
                                'order.createdriver',//创建用车人
                                'user.createdriver',//创建用车人
                                'order.bindCreator',//绑定订单
                                'order.cancelOrder',//取消订单
                                ],

                    'order.doing'=>[
                                'user.driverdisplay',//用车人信息
                                'order.damage',//还车,是否有车损
                                ],
                    
                    'order.finish'=>[
                                'order.settle',//使用结算
                                'order.settleinfo',//还车,是否有车损
                                //todo 行驶轨迹
                                'order.settleIllegal'//违章结算
                                ],

                    'user.list'=>[
                                'user.driverdisplay',//用车人信息
                                'user.modifydriver',//用车人信息修改
                                //todo 行驶轨迹
                                'user.review'//审核修改
                                ],


                    ];
        return $configs;

    }


    public function getRolePerms(){
        $rolePerms = $this->modelsManager->createBuilder()
        ->columns(['menus.*,NOT ISNULL( role2perm.permission_id) has_priv'])
        ->from(['menus'=>'\\Model\\Admin\\UiMenus'])
        ->leftJoin('\\Model\\Admin\\Role2Permission', 'menus.permission_id = role2perm.permission_id AND role2perm.role_id='.$this->id, 'role2perm')
        ->getQuery()
        ->execute();

        $arr = [];
        foreach ($rolePerms->toArray() as $v) {
            // var_dump($v->menus);die();
            $arr[$v->menus->id]=['id'=>$v->menus->id,'name'=>$v->menus->name,'pid'=>$v->menus->pid,'perm_id'=>$v->menus->permission_id,'has_priv'=>$v->has_priv];
        }

        return $arr;
    }

    public function getRolePermsTree(){
        $mapPerms = $this->getRolePerms();
        $tree = self::findChild($mapPerms);

        self::walkTree($tree);
        return $tree;
    }

    private function walkTree(&$tree){
        $hasPriv=0;
        foreach ($tree as &$v) {
            // var_dump($v);
            if (array_key_exists('child',$v)) {
                    $r = self::walkTree($v['child']);
                    if (!!$r) {
                        $v['has_priv']=1;
                        $hasPriv=1;
                    }
                    if (!!$v['has_priv']) {
                        $hasPriv=1;   
                    }

            }
            else
            {
                if (!!$v['has_priv']) {
                    $hasPriv=1;
                }
                
            }            
        }
        return $hasPriv;
        // var_dump($hasPriv);
        // return $hasPriv;


        // if (array_key_exists('child',$tree)) {
        //     foreach ($tree as $v) {
        //        $v['has_priv'] = walkTree($tree);
        //        if (!$v['has_priv']) {
        //            continue;
        //        }
        //        return $v['has_priv'];
        //     }
            
        // }
        // else
        // {
        //     foreach ($tree as $v) {
        //         if (!$v['has_priv']) {
        //             continue;
        //         }
        //        return $v['has_priv'];
        //     }
        // }
    }

    private static function findChild($mapPerms){
        $tree=[];
        $topParents = [];
        $list = $mapPerms;
        foreach( array_keys( $list ) as $key )
        {
            if( $list[$key]['pid'] == 0 )
            {
                continue;
            }
            if( self::putChild( $list , $list[$key] ) )
            {
                unset( $list[$key] );
            }
        }

        return $list;
        

    }
    private static function putChild( &$list , $tree )
    {
        if( empty( $list ) )
        {
            return false;
        }
        foreach( $list as $key => $val )
        {
            if( $tree['pid'] == $val['id'] && $tree['id'] != $tree['pid'] )
            {
                $list[$key]['child'][] = $tree;
                return true;
            }
            if( isset( $val['child'] ) && is_array( $val['child'] ) && !empty( $val['child'] ) )
            {
                if( self::putChild( $list[$key]['child'] , $tree ) )
                {
                    return true;
                }
            }
        }
        return false;
    }

    // private static function find_child($mapPerms) {
    //     $tree=[];

    //     $topParents = [];
    //     foreach ($mapPerms as $id => $v) {
    //         if ($v['pid']) {
    //             if (!array_key_exists($v['pid'], $tree)) {
    //                 $tree[$v['pid']]=$mapPerms[$v['pid']];
    //             }
    //             $tree[$v['pid']]['childs'][$v['id']]=$v;
    //             // unset($)
    //         }
    //         else
    //         {
    //             $topParents[] = $v['id'];
    //         }
    //     }
        
    //     // $tree = self::find_child($mapPerms);
    //     foreach ($tree as $id => $v) {
    //         if (!in_array($id,$topParents)) {
    //             if (in_array($v['pid'],$topParents)) {
    //                 $tree[$v['pid']]['childs'][$v['id']]=$v;
    //                 unset($tree[$id]);
    //             }
    //         }
    //     }
    //     return $tree;
    //     // print_r($tree);die();
    // }

    public static function getPermTree(){
        $allPerms = \Model\Admin\UiMenus::find();
        $mapPerms = [];
        //生成id索引的map结构
        foreach ($allPerms as $v) {
            $mapPerms[$v->id]=['id'=>$v->id,'pid'=>$v->pid,'name'=>$v->name,'perm_id'=>$v->permission_id];
        }


        $tree = self::findChild($mapPerms);
        return $tree;
    }
}
