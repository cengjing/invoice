<?php

class UserAction extends GlobalAction 
{
private $max = 1;
public function index()
{
$this->assign('title','用户列表');
$ret = $this->getFilter('User','A.');
$A = $this->prefix .'user A ';
$B = $this->prefix .'department B ';
$from 		= " FROM $A	";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*) $from $where";
$pageSQL 	= " SELECT A.*,B.title department $from 
							LEFT JOIN $B ON (A.department_id=B.id) $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL );
$this->display();
}
public function _after_insert() 
{
$this->redirect('user/index');
}
public function edit() 
{
$id = intval($_REQUEST['id']);
$model = M('User');
$vo = $model->where("id=$id")->find();
$vo['role'] 			= $vo['id'];
$vo['saleswarehouse'] 	= $vo['id'];
$vo['stock'] 			= $vo['id'];
$this->assign('vo',$vo);
$vo = $model->where(array('id'=>$this->uid))->find();
if($vo['superAdmin'] == 1){
$this->assign('superAdmin',true);
}
$this->assign('title','修改用户');
$this->display();
}
public function _before_insert()
{
$count = M('User')->where(array('status'=>1))->count();
if($count >$this->max)
{
$this->ajaxReturn('','有效用户数量已经超出了最大可用上限，不能再新建',0);
}
S('UserInfo',null);
$uid = intval($_REQUEST['id']);
S('USER_'.$uid.'_ACCESS_FIELDS',null);
}
public function insert()
{
$this->validate();
if(!isset($_REQUEST['id']))
{
if(empty($_REQUEST['password']))
$this->ajaxReturn('','请填写密码',0);
}
$model = D('User');
$vo = $model->where(array('id'=>$this->uid))->find();
if($vo['superAdmin'] == 1)
{
$_POST['isAdmin'] = isset($_REQUEST['isAdmin'])?1:0;
}
if (isset($_POST['id']))
{
$vo = $model->where(array('id'=>$_POST['id']))->find();
if($vo['superAdmin'] == 1)
{
$_POST['isAdmin'] = 1;
}
}
if (false === $model->create ()) {
$this->ajaxReturn('',$model->getError (),0);
}
if(isset($_REQUEST['id'])){
$id = intval($_REQUEST['id']);
$model->save();
}else {
$id = $model->add ();
M('UserOnline')->add(array('uid'=>$id));
}
if ($id !== false) 
{
$this->_insertRoleUser($id);
$this->_insertIvt($id);
S('UserInfo',null);
$result['id'] = $id;
$this->ajaxReturn ( $result,'',1);
}else {
$this->ajaxReturn ( '','新增失败!',0);
}
}
public function _before_update()
{
S('UserInfo',null);
$uid = intval($_REQUEST['id']);
S('USER_'.$uid.'_ACCESS_FIELDS',null);
}
public function update()
{
$this->insert();
}
private function _insertRoleUser($id=null)
{
if(!$id)$id = $_REQUEST['id'];
$model = M('RoleUser');
$model->where("user_id=$id")->delete();
$role = $_REQUEST['role'];
foreach ($role as $v)
{
$map = array();
$map['role_id'] = $v;
$map['user_id'] = $id;
$model->add($map);
}
}
private function _insertIvt($id=null)
{
if(!$id)$id = $_REQUEST['id'];
$model = M('UserSalesWarehouse');
$model->where("uid=$id")->delete();
$salesIvt = $_REQUEST['saleswarehouse'];
foreach ($salesIvt as $v)
{
$map = array();
$map['warehouse_id'] = $v;
$map['uid'] = $id;
$model->add($map);
}
$model = M('UserStock');
$model->where("uid=$id")->delete();
$userIvt = $_REQUEST['stock'];
foreach ($userIvt as $v)
{
$map = array();
$map['warehouse_id'] = $v;
$map['uid'] = $id;
$model->add($map);
}
}
public function resetPassword()
{
    $id = intval($_REQUEST['id']);
    $model = M('User');
    $vo = $model->where(array('id'=>$id))->find();
    if ($vo['superAdmin'] == 1 &&$this->uid != $id){
    	$this->error('您无权修改超级管理员的密码');
    }
    $this->assign('vo',$vo);
    $this->setTitle('修改密码');
    $this->display('changepwd');
}
public function doResetPassword()
{
$id = intval($_REQUEST['id']);
$data['id'] = $id;
$data['password'] = md5($_REQUEST['password']);
if($_REQUEST['password'] == '')
{
$this->error('请填写登录密码');
}
M('User')->save($data);
$this->assign("message",'已修改');
$this->assign("jumpUrl",__URL__.'/index');
$this->success('修改密码成功');
}
}?>