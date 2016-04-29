<?php

class GroupModel extends Model 
{
public function getPerm($uid)
{
$prefix = C('DB_PREFIX');
$groups = $this->where("status=1")->order('seq ASC')->select();
$perms = M('GroupUser')->where("user_id=$uid")->select();
$model = M('GroupModule');
$B = $prefix.'modules B';
foreach ($groups as $v)
{
$group_id = $v['id'];
$merge[] = array('group_id'=>$group_id,'title'=>$v['title'],'module_id'=>0);
$vo = $model->query("SELECT B.id,B.title FROM __TABLE__ A LEFT JOIN $B ON(A.module_id=B.id) WHERE A.group_id=$group_id ORDER BY B.seq ASC");
foreach ($vo as $val){
$merge[] = array('group_id'=>$group_id,'module_id'=>$val['id'],'title'=>$val['title'],'sub'=>true);
}
}
foreach ($merge as &$v){
$setting = false;
foreach ($perms as $val)
{
if ($v['group_id'] == $val['group_id'] &&$v['module_id'] == $val['module_id'])
{
if($val['all'] == 1){
$v['perm'] = '<span style="color:blue;">[全部]</span>';
}else{
if ($val['department'] != ''){
$v['perm'] .= '<span style="color:blue;">[部门]</span>：'.getUserDepartmentById($val['department']).'&nbsp;&nbsp;<span style="color:gray;">('.(($val['all_status']==1)?'离、在职':'在职').')</span>';
}
if($val['others'] != ''){
$v['perm'] .= '<span style="color:blue;">[成员]</span>：'.getUserInfoById($val['others']).'&nbsp;&nbsp;<span style="color:gray;">('.(($val['all_status']==1)?'离、在职':'在职').')</span>';
}
if ($val['department'] == ''&&$val['others'] == ''){
$v['perm'] = '<span style="color:blue;">[本人]</span>';
}
}
$setting = true;
break;
}
}
if($setting == false) {
if($v['module_id'] == 0)
$v['perm'] = '<span style="color:blue;">[本人]</span>';
else 
$v['perm'] = '<span style="color:blue;">[等同上级权限]</span>';
}
}
$ret['perms'] = $perms;
$ret['merge'] = $merge;
return $ret;
}
public function getAssistant($uid)
{
$vo = M('User')->where(array('id'=>$uid))->find();
$department_id = $vo['department_id'];
$vo = $this->where(array('name'=>'sales_assistant'))->find();
if($vo == null) return;
$group_id = $vo['id'];
$vo = M('GroupUser')->where(array('user_id'=>$uid,'group_id'=>$group_id,'module_id'=>0))->find();
$perm = array();
$model = D('User');
if($vo){
$perm['all'] = $vo['all'];
$perm['department'] = $vo['department'];
$perm['others'] = $vo['others'];
}
if (null == $perm) {
$users = $model->getUsersList ('');
}else if ($perm['all'] == 1) {
$users = $model->getUsersList( '','all');
$all = true;
}else if ( !empty($perm['department']) ) {
$users = $model->getUsersList( $perm,'department');
}else if ( !empty($perm['others']) ) {
$users = $model->getUsersList( $perm,'others');
}else {
$users = $model->getUsersList ('');
}
foreach ($users as $u){
if($u['id'] != $uid){
$tmp[] = $u;
}
}
$ret['sales'] = $tmp;
$model = D('Department');
$vo = M('GroupUser')->where(array('group_id'=>$group_id,'module_id'=>0))->select();
foreach ($vo as $v)
{
if($v['user_id'] != $uid)
{
if($v['all'] == 1)
{
$ret['assistants'][] = $v['user_id'];
}
else
{
if( !empty($v['department']) )
{
$dpts = explode( ',',$v['department'] );
foreach ($dpts as $val){
if($val != ''){
foreach ($model->getAllChildList($val) as $dpt){
array_push($dpts,$dpt);
}
}
}
foreach ($dpts as $val){
if($val == $department_id){
$ret['assistants'][] = $v['user_id'];
}
}
}
if( !empty($v['others']) )
{
$teams = explode( ',',$v['others'] );
foreach ($teams as $val)
{
if($val == $uid){
$ret['assistants'][] = $v['user_id'];
}
}
}
}
}
}
$ret['assistants'] = array_unique($ret['assistants']);
return $ret;
}
public function getAssistantModule($val)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'modules A';
$B = $prefix.'group_module B';
$C = $prefix.'group C';
$Sql = "SELECT A.title,A.id FROM $A LEFT JOIN $B ON(A.id=B.module_id) LEFT JOIN $C ON (B.group_id=C.id) WHERE C.name='sales' ORDER BY A.seq ASC";
$vo = $this->query($Sql);
$t = explode(',',$val);
foreach ($vo as &$v){
foreach ($t as $l){
if ($l!=''&&$l == $v['id']){
$v['_checked'] = true;
break;
}
}
}
return $vo;
}
}?>