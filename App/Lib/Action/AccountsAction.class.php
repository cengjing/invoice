<?php

class AccountsAction extends GlobalAction 
{
public function index()
{
$this->_getAccounts();
$this->setTitle('客户管理');
$this->display();
}
public function add()
{
$this->setTitle('添加客户');
$this->display();
}
public function insert()
{
$this->validate();
$_POST['status'] = isset($_REQUEST['status'])?1:0;
$model = D('Accounts');
if(getConfigValue('company_name_repeat') == 0)
{
$map['company'] = $_REQUEST['company'];
if(isset($_REQUEST['id']))
{
$map['id'] = array('neq',$_REQUEST['id']);
}
$vo = $model->where($map)->find();
if($vo)
{
$this->ajaxReturn ( '','公司名称不能重复！',0);
}
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
$vo = D('Accounts')->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->setTitle($vo['base']['company']);
$this->assign ( 'vo',$vo['base'] );
}
public function show()
{
$id = intval($_REQUEST['id']);
$access = $this->__get('access');
$M = M();
if(isset($access['CREDIT']['INDEX']))
{
$A = $this->prefix .'discount A';
$B = $this->prefix .'accounts B';
$from 		= " FROM $A ";
$Sql 	= " SELECT A.type_id,A.discount,B.company $from LEFT JOIN $B ON (A.company_id=B.id) 
			WHERE B.id=$id AND A.status=1 ORDER BY A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('discountList',$vo);
}
if(isset($access['ACTIVITY']['INDEX']))
{
$A = $this->prefix .'activity A';
$B = $this->prefix .'contacts B';
$Sql = "SELECT B.name,A.cTime,A.abstract,A.id,A.contact_id FROM $A LEFT JOIN $B ON (A.contact_id=B.id) WHERE A.company_id=$id ORDER By A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('activityList',$vo);
}
if (isset($access['QUOTES']['INDEX']))
{
$A = $this->prefix .'quotes A';
$Sql = "SELECT A.id,A.cTime,A.abstract,A.status FROM $A WHERE A.company_id=$id ORDER By A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('quotesList',$vo);
}
if (isset($access['SALESORDER']['INDEX']))
{
$A = $this->prefix .'salesorder A';
$Sql = "SELECT A.id,A.cTime,A.abstract,A.status FROM $A WHERE A.company_id=$id AND A.status IN (1,2,3,5) ORDER By A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('salesorderList',$vo);
}
if (isset($access['INVOICE']['INDEX']))
{
$A = $this->prefix .'invoice A';
$B = $this->prefix .'contacts B';
$C = $this->prefix .'accounts C';
$Sql = "SELECT A.cTime,A.amount,A.id,A.abstract,A.status FROM $A 
					LEFT JOIN $B ON (A.contact_id=B.id) 
					LEFT JOIN $C ON (B.company_id=C.id) 
					WHERE C.id=$id AND A.status IN (1,2,4)  ORDER By A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('invoiceList',$vo);
$A = $this->prefix .'receipt A';
$Sql = "SELECT A.cTime,A.amount,A.id,A.status,A.uid FROM $A
					WHERE A.company_id=$id AND A.status IN (1,2,3,5) ORDER By A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('receiptList',$vo);
}
$vo = D('Accounts')->stat($id);
$this->assign('arrears',$vo['arrears']);
$this->assign('invoice',$vo['invoice']);
$this->assign('credit',D('Credit')->getAccountCredit($id));
$model = D('AccountsStat');
$vo = $model->where(array('company_id'=>$id))->find();
$this->assign('order_amount',formatCurrency($vo['order_amount']));
$this->assign('order_quant',$vo['order_quant']);
$this->assign('unfinished_order_quant',$vo['unfinished_order_quant']);
$this->assign('last_order',$vo['last_order']);
$this->assign('contacts_quant',$vo['contacts_quant']);
$this->assign('activiry_quant',$vo['activiry_quant']);
$this->assign('last_activity',$vo['last_activity']);
$this->display();
}
public function showContacts()
{
$id = intval($_REQUEST['id']);
$vo = D('Accounts')->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->setTitle('联系人列表 - '.$vo['base']['company']);
$vo = M('contacts')->where("company_id=$id")->select();
$this->assign('list',$vo);
$this->assign('id',$id);
$this->display('showContacts');
}
public function filter()
{
$ret = $this->getFilter('Accounts','A.');
$perm = $ret['perm'];
$uid = '';
if ($perm['all'] !== true){
foreach ($perm['users'] as $v){
$uid .= ($uid==''?'':',').$v['id'];
}
}
$A = $this->prefix .'accounts A ';
$from 		= " FROM $A ";
$where 		= " WHERE A.status=1 ";
if(!empty($uid)){
$where 		.= " AND A.assignto IN ($uid) ";
}
if(!empty($ret['where'])){
$where  	.= ' AND '.$ret['where'];
}
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.* $from $where";
$this->_pageList ( $countSQL,$pageSQL);
$this->display();
}
private function _getAccounts()
{
$ret = $this->getFilter('Accounts','A.');
$A = $this->prefix .'accounts A ';
$from 		= " FROM $A ";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.* $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
}
public function getById()
{
if(!$this->isAjax())return;
$id = intval($_REQUEST['id']);
$vo = D('Accounts')->getPerm($id);
if($vo == false)
{
$this->ajaxReturn('','',0);
}
$this->ajaxReturn($vo['base'],'',1);
}
public function editAll()
{
$this->setTitle('客户批量修改');
$items = $_REQUEST['items'];
if(!empty($items)){
$map['id'] = array('IN',$items);
$vo = M('Accounts')->where($map)->field('company')->select();
$this->assign('list',$vo);
$this->assign('items',$items);
}
$this->display();
}
public function updateAll()
{
$items = $_REQUEST['items'];
if(empty($items)){
if(!empty($_REQUEST['from_id'])) $map['assignto'] = $_REQUEST['from_id'];
if(!empty($_REQUEST['province'])) $map['province'] = $_REQUEST['province'];
if(!empty($_REQUEST['regioncode'])) $map['regioncode'] = $_REQUEST['regioncode'];
if(!empty($_REQUEST['postalcode'])) $map['postalcode'] = $_REQUEST['postalcode'];
}else{
$map['id'] = array('IN',$items);
}
if(count($map) == 0){
$this->ajaxReturn ( '','错误的筛选条件，必须选择至少一个条件!',0);
}
$data['assignto'] = $_REQUEST['to_id'];
if(count($data) == 0){
$this->ajaxReturn ( '','错误的修改条件，必须选择至少一个条件!',0);
}
$ret = M('Accounts')->where($map)->save($data);
$this->ajaxReturn ( '','完成批量修改!',1);
}
public function statInfo()
{
$id = intval($_REQUEST['id']);
$this->assign('id',$id);
$model = M('Salesorder');
$vo = $model->query("SELECT SUM(total-collection) sum FROM __TABLE__ WHERE company_id=$id AND status=3 AND collection<total");
if(empty($vo[0]['sum']))$vo[0]['sum'] = 0;
$this->assign('arrears',formatCurrency($vo[0]['sum']));
$vo = $model->query("SELECT SUM(total-invoice) sum FROM __TABLE__ WHERE company_id=$id AND status=3 AND invoice<total");
if(empty($vo[0]['sum']))$vo[0]['sum'] = 0;
$this->assign('invoice',formatCurrency($vo[0]['sum']));
$model = D('AccountsStat');
$model->stat($id);
$vo = $model->where(array('company_id'=>$id))->find();
$this->assign('order_amount',formatCurrency($vo['order_amount']));
$this->assign('order_quant',$vo['order_quant']);
$this->assign('unfinished_order_quant',$vo['unfinished_order_quant']);
$this->assign('last_order',$vo['last_order']);
$this->assign('contacts_quant',$vo['contacts_quant']);
$this->assign('activiry_quant',$vo['activiry_quant']);
$this->assign('last_activity',$vo['last_activity']);
$this->display('statInfo');
}
}
?>