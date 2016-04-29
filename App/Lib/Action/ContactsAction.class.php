<?php

class ContactsAction extends GlobalAction 
{
public function index()
{
$this->_getContacts();
$this->setTitle('联系人列表');
$this->display();
}
public function add()
{
$this->setTitle('新建联系人');
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
$model = D('Contacts');
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
$vo = D('Contacts')->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->setTitle($vo['base']['name'].'-'.$vo['base']['company']);
$this->assign ( 'vo',$vo['base'] );
}
public function show()
{
$id = intval($_REQUEST['id']);
$this->assign('stat',D('Contacts')->stat($id));
$access = $this->__get('access');
$M = M();
if(isset($access['ACTIVITY']['INDEX']))
{
$A = $this->prefix .'activity A';
$B = $this->prefix .'contacts B';
$Sql = "SELECT B.name,A.cTime,A.abstract,A.id,A.contact_id FROM $A LEFT JOIN $B ON (A.contact_id=B.id) WHERE A.contact_id=$id ORDER By A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('activityList',$vo);
}
if (isset($access['QUOTES']['INDEX']))
{
$A = $this->prefix .'quotes A';
$Sql = "SELECT A.id,A.cTime,A.abstract FROM $A WHERE A.contact_id=$id ORDER By A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('quotesList',$vo);
}
if (isset($access['SALESORDER']['INDEX']))
{
$A = $this->prefix .'salesorder A';
$Sql = "SELECT A.id,A.cTime,A.abstract FROM $A WHERE A.contact_id=$id ORDER By A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('salesorderList',$vo);
}
$this->display();
}
public function filter()
{
if(!$this->isAjax())return;
$this->_getContacts();
$this->display();
}
private function _getContacts()
{
$ret = $this->getFilter('Contacts','A.');
$perm = D('GroupUser')->getUsers($this->getActionName());
$uid = '';
if ($perm['all'] !== true){
foreach ($perm['users'] as $v){
$uid .= ($uid==''?'':',').$v['id'];
}
}
$A = $this->prefix.'contacts A ';
$B = $this->prefix.'accounts B ';
$from 		= " FROM $A LEFT JOIN $B ON A.company_id=B.id ";
if(!empty($uid)){
$where 		= " WHERE B.assignto IN ($uid) ";
}
if(!empty($ret['where'])){
$where  	.= (empty($where)?' WHERE ':' AND ').$ret['where'];
}
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.id,A.status,A.sex,A.blacklist,A.name,A.duty,A.company_id,A.regioncode,A.postalcode,
		A.address,A.mobilephone,A.phone,A.description,B.company 
		$from $where GROUP BY A.cTime DESC";
$this->_pageList ( $countSQL,$pageSQL );
}
public function getById()
{
if(!$this->isAjax())return;
$id = intval($_REQUEST['id']);
$vo = D('Contacts')->getPerm($id);
if(!$vo)
{
$this->ajaxReturn('','',0);
}
$vo['base']['company'] = $vo['account']['company'];
$this->ajaxReturn($vo['base'],'',1);
}
}
?>