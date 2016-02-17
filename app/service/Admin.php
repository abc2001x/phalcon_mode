<?php
namespace Service;

class Admin
{   


    public static function getAllPermsTree(){
        return \Model\Admin\Role::getPermTree();
    }

    public static function getUserPermsTree($user){
        $tree = $user->role->getRolePermsTree();


        return $tree;
    }
}
