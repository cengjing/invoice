<?php

class MembersAction extends GlobalAction 
{
public function index()
{
$this->setTitle('组织结构');
$departments = D('Department')->getList('',false);
$A = $this->prefix .'user A ';
$B = $this->prefix .'user_online B';
$sql = "SELECT A.id,A.sex,A.name,A.mobilephone,A.email,A.abstract,A.department_id,B.activeTime 
				FROM $A LEFT JOIN $B ON(A.id=B.uid) WHERE A.status=1 ORDER BY A.position ASC";
$users = M()->query($sql);
import('ORG.Util.Date');
$cTime = time();
$date = new Date($cTime);
$newUsers = $arr = array();
$c = $all = 0;
foreach ($users as $v)
{
$all++;
if($v['activeTime']>=($cTime-61))
{
$c++;
$v['online'] = true;
}
$v['activeTime'] = (intval($v['activeTime']) != 0)?$date->timeDiff(intval($v['activeTime'])):'';
$arr[] = $v;
}
$tmp = array();
foreach ($departments as $val)
{
foreach ($arr as $v)
{
if ($val['id'] == $v['department_id'])
{
$val['members'][] = $v;
}
}
$tmp[] = $val;
}
$ret = array();
foreach ($tmp as $val)
{
$val['padding'] = 20 * $val['level'];
$ret[] = $val;
}
$this->assign('departments',$ret);
$this->assign('online',$c);
$this->assign('allStaff',$all);
$this->display();
}
public function show()
{
$uid = intval($_REQUEST['id']);
D('Group')->getAssistant($uid);
$vo = D('User')->getInfo($uid);
if($vo == false)
{
$this->error('您查看的用户不存在。');
}
$this->assign('vo',$vo['base']);
$this->setTitle('用户 - '.$vo['base']['name']);
$vo = D('Role')->getMultiList($uid);
foreach ($vo as $v)
{
if($v['_checked'] == true)
{
$ret[] = $v;
}
}
$this->assign('roleList',$ret);
$model = D('User');
$vo = $model->getMultiSalesWarehouse($uid);
unset($ret);
foreach ($vo as $v)
{
if($v['_checked'] == true)
{
$ret[] = $v;
}
}
$this->assign('salesWarehouseList',$ret);
$vo = $model->getMultiStock($uid);
unset($ret);
foreach ($vo as $v)
{
if($v['_checked'] == true)
{
$ret[] = $v;
}
}
$this->assign('stockList',$ret);
if(session('?administrator') ||$uid == $this->uid){
$access = RBAC::getAccessList ($uid);
$A = $this->prefix.'node A';
$Sql = "SELECT A.* FROM $A WHERE A.pid!=0 AND bind=0 ORDER BY seq ASC";
$vo = M()->query($Sql);
$tree = list_to_tree ( $vo,'id','pid','_child',1 );
foreach ($tree as $v){
if( $v['level']==2 &&isset($access['APP'][strtoupper( $v['name'] )]) ){
$perm[$v['name']]['module'] = $v['title'];
foreach ($v['_child'] as $val){
if($val['level']==3 &&isset($access['APP'][strtoupper( $v['name'] )][strtoupper( $val['name'] )])){
$perm[$v['name']]['action'] .= (isset($perm[$v['name']]['action'])?'，':'').$val['title'];
}
}
}
}
$this->assign('actionPerm',$perm);
$model = D('Group');
$ret = $model->getPerm($uid);
$this->assign('groups',$ret['merge']);
$this->assign('ss',session('sales_assistant'));
}
if($uid == $this->uid)
{
$vo = M('UserEmail')->where(array('uid'=>$uid))->find();
$this->assign('email',$vo);
$this->display('edit');
}else {
$this->display('show');
}
}
public function update()
{
$uid = intval($_REQUEST['id']);
if($uid != $this->uid)
{
$this->error('您只能修改自己的个人档案');
}
$model = D('User');
if (!empty ( $_FILES )) {
$model->doChangeFace($uid);
}
$data['id']				= $uid;
$data['email']			= $_POST['email'];
$data['mobilephone']	= $_POST['mobilephone'];
$model->save($data);
$model = M('UserEmail');
$vo = $model->where(array('uid'=>$uid))->find();
$data['smtp_email'] 		= $_POST['smtp_email'];
$data['smtp_host'] 			= $_POST['smtp_host'];
$data['smtp_port'] 			= $_POST['smtp_port'];
$data['smtp_name'] 			= $_POST['smtp_name'];
$data['smtp_password'] 		= $_POST['smtp_password'];
if($vo){
$data['id'] = $vo['id'];
$model->save($data);
}else{
$data['uid'] = $uid;
$model->add($data);
}
$this->success('成功保存个人档案');
}
public function changePassword()
{
$id = intval($_REQUEST['id']);
if($id != $this->uid)
{
$this->error('您只能修改您本人的登录密码');
}
$vo = M('User')->where("id=$id")->find();
$this->assign('vo',$vo);
$this->setTitle('用户修改密码');
$this->display('changepwd');
}
public function doChangePassword()
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
$this->assign("jumpUrl",__URL__.'/show/id/'.$id);
$this->success('修改密码成功');
}
public function sendEmail()
{
$uid = $this->uid;
$subject = '测试邮箱设置成功';
$body = '这是一封测试邮件，如果您收到这份邮件，说明您的Smtp邮箱设置是正确的。';
$model = D('UserEmail');
$vo = $model->where(array('uid'=>$uid))->find();
if($vo === null)
{
$this->ajaxReturn('','发件人的信息不正确',0);
}
$address = $vo['smtp_email'];
$ret = $model->sendEmail($uid,$subject,$body,$address);
if($ret){
$this->ajaxReturn('','邮件已发送，请查收你的邮箱',1);
}else{
$this->ajaxReturn('','邮件已发送失败：'.$model->error_info,0);
}
}
}
?>