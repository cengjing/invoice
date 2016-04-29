<?php

class ActivityAction extends GlobalAction
{
public function index()
{
$this->setTitle('客户联系');
$ret = $this->getFilter('Activity','A.');
$perm = D('GroupUser')->getUsers($this->getActionName());
$uid = '';
if ($perm['all'] !== true){
foreach ($perm['users'] as $v){
$uid .= ($uid==''?'':',').$v['id'];
}
}
if(!empty($uid)){
$where 		= " WHERE B.assignto IN ($uid) ";
}
if(!empty($ret['where'])){
$where  	.= (empty($where)?' WHERE ':' AND ').$ret['where'];
}
$A = $this->prefix .'activity A ';
$D = $this->prefix .'accounts B ';
$E = $this->prefix .'contacts C ';
$from 		= " FROM $A
						LEFT JOIN $D ON A.company_id=B.id
						LEFT JOIN $E ON A.contact_id=C.id";
$countSQL 	= " SELECT COUNT(*) $from $where";
$pageSQL 	= " SELECT A.id,A.cTime,A.activity_time,A.uid,A.abstract,B.company,C.name contact 
						$from
						$where 
						ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL );
$this->display();
}
public function add()
{
$this->setTitle('新建客户联系');
$cid = intval($_REQUEST['cid']);
if($cid)
{
$vo = D('Accounts')->getPerm($cid);
if($vo !== false)
{
$vo['base']['company_id'] = $vo['base']['id'];
$this->assign('data',$vo['base']);
}
}
$this->display();
}
public function insert()
{
$this->validate();
$_POST['status'] = isset($_REQUEST['status'])?1:0;
$model = D('Activity');
if(!isset($_REQUEST['id']))
{
$_POST['uid'] = $this->uid;
}
if($_REQUEST['activity_time'] != '')
{
$date = explode('-',$_REQUEST['activity_time']);
$_POST['activity_time'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
}
else
{
$_POST['activity_time'] = time();
}
if (false === $model->create ()) {
$this->ajaxReturn('',$model->getError (),0);
}
if(isset($_REQUEST['id']))
{
$id = intval($_REQUEST['id']);
$model->save();
}
else
{
$id = $model->add ();
}
if ($id !== false) {
$result['id'] = $id;
$vo = $model->where(array('id'=>$id))->find();
$company_id = $vo['company_id'];
D('AccountsStat')->stat($company_id,'contacts');
$this->ajaxReturn ( $result,'',1);
}else {
$this->ajaxReturn ( '','新增失败!',0);
}
}
public function update()
{
$this->insert();
}
public function edit()
{
$this->_before_show();
$this->setTitle('修改联系人');
$this->display();
}
public function _before_show()
{
$id = intval($_REQUEST['id']);
$vo = D('Activity')->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->setTitle($vo['base']['contact'].'-'.$vo['base']['company']);
$this->assign ( 'vo',$vo['base'] );
}
public function show()
{
$this->display();
}
}

?>