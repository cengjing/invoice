<?php

class GroupUserModel extends BaseModel 
{
protected $tableName = 'group_user';
public function getUsers( $module ,$uid = null)
{
$module = strtolower( $module );
if($module == 'sod'){
$module = 'salesorder';
}
if(!$uid){
$uid = session(C('USER_AUTH_KEY'));
}
$map['name'] = $module;
$cacheName = "userModulePerm_$uid";
$result = S($cacheName);
if (!$result[$module])
{
$model = M('Group');
$vo = $model->where($map)->find();
$showAll 		= $vo ['showAll'];
$showActivity 	= $vo ['showActivity'];
$model = D('User');
$all = false;
if ( $showAll == 1 ||($this->adminModule($module) &&session('?administrator')) ) 
{
$users = $model->getUsersList ('','all',$showActivity);
$all = true;
}
else 
{
$vo = M('Modules')->where(array('module'=>$module))->find();
$module_id = $vo['id'];
$vo = M('GroupUser')->where(array('module_id'=>$module_id,'user_id'=>$uid))->find();
if($vo == null){
$vo = M('GroupModule')->where(array('module_id'=>$module_id))->find();
$group_id = $vo['group_id'];
$vo = M('GroupUser')->where(array('group_id'=>$group_id,'user_id'=>$uid,'module_id'=>0))->find();
}
$perm = array();
if($vo){
$perm['all'] = $vo['all'];
$perm['department'] = $vo['department'];
$perm['others'] = $vo['others'];
$allStatus = $vo['all_status'];
}
if (null == $perm) {
$users = $model->getUsersList ('');
}else if ($perm['all'] == 1) {
$users = $model->getUsersList( '','all',$showActivity );
$all = true;
}else if ( !empty($perm['department']) ) {
$users = $model->getUsersList( $perm,'department',($showActivity == 1 ||$allStatus == 1) ?1 : 0);
}else if ( !empty($perm['others']) ) {
$users = $model->getUsersList( $perm,'others',($showActivity == 1 ||$allStatus == 1) ?1 : 0);
}else {
$users = $model->getUsersList ('');
}
}
$p['users'] = $users;
$p['all'] = $all;
$result[$module] = $p;
S($cacheName,$result,3600);
}
return $result[$module];
}
private function adminModule($module)
{
$module = strtolower($module);
$adminModule = array('group','check','setting','payment','verification','accountpayable');
return in_array($module,$adminModule);
}
}?>