<?php
namespace Model\Admin;
use Phalcon\Mvc\Model\Behavior\SoftDelete;
use Phalcon\Paginator\Adapter\QueryBuilder as PaginatorQueryBuilder;
use Plugins\Common;

class User extends \Phalcon\Mvc\Model
{
	public $id ;
	public $name ;
	public $password;
    public $level;
    public $mail;
    public $creator_id;
    public $ctime;
    public $deleted;

    public $role_id;

    public $type;
    const MERCHANT = 1;
    const ADMIN = 0;

    const DELETED = 1;

    const NOT_DELETED = 0;

    public function beforeCreate()
    {
        // 事件函数,可在此预先处理一些插入动作
        $this->ctime = time();
        $this->deleted = self::NOT_DELETED;

        $creator = \Library\Utils::getAdminCreator();

        if($creator) {
            $this->creator_id = $creator->id;
        }
        
    }


    public function getSource()
    {
        return "admin_user";
    }
    public function checkPassword($password){

        return $password == $this->password;
        
    }

    public function isActive(){
        return $this->deleted;
    }

    public function getAuthData()
    {
        $authData = new \stdClass();
        $authData->id = $this->id;
        $authData->admin_session = true;
        $authData->email = $this->email;
        $authData->name = $this->name;
        return $authData;
    }

	public function initialize()
    {
        // $this->belongsTo('robots_id', '\Model\AdminRole', 'id');
        // $this->hasOne('role_id','AdminRole','id');
        $this->hasOne('role_id','\\Model\\Admin\\Role','id',array("alias"=>'role'));
        $this->addBehavior(
            new SoftDelete(
                array(
                    'field' => 'deleted',
                    'value' => self::DELETED
                )
            )
        );
    }

    public function onConstruct()
    {
        // echo "每一个实例在创建的时候单独进行初始化".PHP_EOL;
    }


    /**
     * 通过用户名获取用户值
     * @param $username
     * @param $password
     */
    public function getUserByUsername($username, $password) {

        return $this->findFirst(array(
            'conditions' => "name = :username: AND password = :password: AND deleted = :deleted:",
            'bind' => array('username'=>$username, 'password' => Common::generatePassword($password), 'deleted'=>self::NOT_DELETED)
        ));
    }

    /**
     * 更新
     *
     * @param $userId
     * @param $params
     */
    public function updateUserByUser($userId, $params) {
        $row = $this->findFirst(array('id' => $userId));
        foreach ($params as $key => $val) {
            $row->$key = $val;
        }
        $row->save();
    }

    public static function add($params){
        $fields = [
                    'name'=>'required',//用戶名
                    'password'=>'required',//密碼
                    'mail'=>'',//郵件
                    'type'=>'',
                    'role_id'=>'',
                    ];
        $result = ['result'=>false,'user'=>null];

        $user = new self();
        foreach ($fields as $k => $v) {
            if ($fields[$k] == 'required' && !array_key_exists($k,$params)) {
                return $result;
            }
            if (array_key_exists($k,$params)) {
                if ($k=='password') {
                    $user->{$k}=Common::generatePassword($params[$k]);
                }
                else
                {
                    $user->{$k}=$params[$k];    
                }
                
            }
            
        }
        
        $result = ['result'=>$user->save(),'user'=>$user];
        return $result;
    }
    

    public function modifyMerchant($params){
        $fields = [
                    'password',
                    'stores',
                    'privs'
                    ];

        // $transaction = \Bootstrap::getApp()->getDi()->getTransactions()->get();
        if (array_key_exists('password',$params)) {
            //修改密码
            $this->password = \Plugins\Common::generatePassword($params['password']);
            $this->save();
        }

        if (array_key_exists('stores',$params)) {
            //修改门店权限
            $result = $this->addMerchantStore($params['stores']);
        }

        if (array_key_exists('privs',$params)) {
            //修改操作权限
            $this->role->setRolePermission($params['privs']);
        }
        return true;
    }

    public function deleteMerchant(){
        $this->deleted = self::DELETED;
        return $this->save();
    }

    public static function addMerchant($name,$password,$stores,$privs){
        $type=self::MERCHANT;

        $user = new self();
        $transaction = \Bootstrap::getApp()->getDi()->getTransactions()->get();
        
        $user->setTransaction($transaction);
        $user->name = $name;
        $user->password =  \Plugins\Common::generatePassword($password);
        $user->type = $type;

        if (!$user->save()) {
            foreach ($user->getMessages() as $message) {
                $transaction->rollback($message->getMessage());
            }
            return false;
        }

        $result = $user->addMerchantStore($stores);


        $role = new \Model\Admin\Role();
        $role->setTransaction($transaction);
        $role->name = $name;
        if(!$role->save()){
            foreach ($user->getMessages() as $message) {
                $transaction->rollback($message->getMessage());
            }
            return false;
        }
        $result = $role->setRolePermission($privs);
        if (!$result['result']) {
            $transaction->rollback();
            return false;
        }
        $user->role_id = $role->id;
        if(!$user->save()){
            foreach ($user->getMessages() as $message) {
                $transaction->rollback($message->getMessage());
            }

            return false;
        }


        $transaction->commit();

        return true;
        
    }

    private function deleteMerchantStore(){
        $phql = "DELETE FROM \\Model\\Merchant\\Merchant2Store WHERE user_id=:user_id:";
        $result = ['result'=>false,'message'=>''];

        try {
            $r = $this->getModelsManager()->executeQuery(
                $phql,
                array(
                    'user_id' => $this->id
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

    public function getMerchanStore(){
        $builder = $this->modelsManager->createBuilder();
        $builder->columns(['store.*'])
        ->from(['store'=>'\\Model\\Store\\StoreInfo'])
        ->leftjoin('\\Model\\Merchant\\Merchant2Store','store.id=mert2user.store_id','mert2user')
        ->where('mert2user.user_id =:user_id:',['user_id'=>$this->id]);
        return $builder->getQuery()->execute();
    }

    public function addMerchantStore($storeIds){

        $result = ['result'=>false,'message'=>null];
        if (!is_array($storeIds)) {
            $result = ['result'=>false,'message'=>'參數必須是數組,如[1,2,3]'];
        }

        $formatValue = '(%s,%s)';
        $arrValues = [];
        

        foreach ($storeIds as $v) {
            $arrValues[] = sprintf($formatValue,$this->id,$v);
        }

        $strValues = implode(',',$arrValues);

        $phql = 'INSERT INTO merchant2store VALUES '.
                $strValues;
        // $phql = 'INSERT INTO admin_role2permission (role_id,permission_id) VALUES (1,2),(1,1)';
        // echo $phql;
        try {
            $this->deleteMerchantStore();
            $r = $this->getReadConnection()->query($phql);  
            $result['result']=!!$r;  
        } catch (\Exception $e) {
            $result['result']=false;   
            $result['message'] = $e->getMessage(); 
        }
        
        return $result;


    }
}
