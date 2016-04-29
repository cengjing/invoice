<?php

class AllotAction extends WorkflowAction 
{
public function index()
{
$this->setTitle('调拨单列表');
$ret = $this->getFilter('Allot','A.');
$A = $this->prefix .'allot A ';
$from 		= " FROM $A ";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.* $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL );
$this->display();
}
public function add()
{
$this->setTitle('新建调拨单');
$this->display();
}
public function insert()
{
$this->validate();
if($_REQUEST['out_id'] == $_REQUEST['in_id'])
{
$this->ajaxReturn('','调拨仓库与目的仓库不能相同。',0);
}
$gp = array();
foreach ($_REQUEST['pid'] as $k=>$v)
{
$data = array();
$data['product_id'] 	= $v;
$data['qty'] 			= floatval($_REQUEST['qty'][$k]);
$gp[] = $data;
}
if (count($gp) == 0)
{
$this->ajaxReturn('','请填写订单明细。',0);
}
if(!isset($_REQUEST['id']))
{
$_POST['uid'] = $this->uid;
}
$id = $this->save('Allot');
if ($id !== false) {
$model = M('AllotDetails');
$model->where("allot_id=$id")->delete();
foreach ($_REQUEST['pid'] as $k=>$v){
$data = array();
$data['allot_id'] = $id;
$data['product_id'] = $v;
$data['qty'] = $_REQUEST['qty'][$k];
$model->add($data);
}
$result['id'] = $id;
$this->ajaxReturn($result,'',1);
}else {
$this->error ( '新增失败!');
}
}
public function update()
{
$this->insert();
}
public function _before_edit()
{
$id = intval($_REQUEST['id']);
$vo = D('Allot')->getInfo($id);
$this->assign('vo',$vo['base']);
$this->setTitle('修改调拨单-'.$vo['base']['id']);
$details = D('Allot')->getDetails($id);
$this->assign('details',$details);
}
public function show()
{
$this->_before_edit();
$vo = $this->__get('vo');
$this->setTitle('调拨单-'.$vo['id']);
$this->display();
}
public function edit() 
{
$this->display ();
}
public function _before_approve()
{
$id = intval($_REQUEST['id']);
$Allot = D('Allot');
$Warehouse = D('Warehouse');
$info = $Allot->getInfo($id);
$info = $info['base'];
$map['id'] = $info['out_id'];
$vo = M('WarehouseName')->where($map)->find();
$dbfield = $vo['dbfield'];
$A = $this->prefix.'allot_details A';
$B = $this->prefix.'warehouse B';
$C = $this->prefix.'products C';
$SQL = "SELECT A.product_id,A.qty,B.$dbfield stock_qty,C.productname,C.catno 
								FROM $A
								LEFT JOIN $B ON A.product_id=B.product_id
								LEFT JOIN $C ON A.product_id=C.id
								WHERE A.allot_id=$id";
$details = M()->query($SQL);
if(null == $details)
{
$this->error('没有找到需要调拨的产品请确认！');
}
foreach ($details as $v)
{
if( floatval($v['qty']) >floatval($v['stock_qty']) )
{
$this->error ($v['productname'].','.$v['catno'].',库存数量不足！');
}
}
$data['cTime']			=	time();
$data['type']			=	2;
$data['status']			=	1;
$data['warehouse_id']	=	$info['out_id'];
$data['from_id']		=	$id;
$data['uid']			=	$info['uid'];
$data['company']		=	$info['company'];
$data['name']			=	$info['name'];
$data['regioncode']		=	$info['regioncode'];
$data['postalcode']		=	$info['postalcode'];
$data['address']		=	$info['address'];
$data['mobilephone']	=	$info['mobilephone'];
$data['phone']			=	$info['phone'];
$data['abstract']		=	'调拨单'.$id;
$model = D('Delivery');
$delivery_id = $model->add($data);
$delivery_id = $model->saveId($delivery_id);
$model = M('DeliveryDetails');
foreach ($details as $v)
{
$warehouseQty = $Warehouse->Dec($dbfield,$v['product_id'],$v['qty']);
$data = array();
$data['delivery_id']	= $delivery_id;
$data['product_id']		= $v['product_id'];
$data['qty']			= $v['qty'];
$data['stock_qty']		= $warehouseQty;
$model->add($data);
}
unset($data);
$data['cTime']			=	time();
$data['uid']			=	$info['uid'];
$data['type']			=	2;
$data['warehouse_id']	=	$info['in_id'];
$data['status']			=	1;
$data['abstract']		=	'调拨单'.$id;
$model = D('Entry');
$entry_id = $model->add($data);
$entry_id = $model->saveId($entry_id);
$model = M('EntryDetails');
foreach ($details as $v)
{
$data = array();
$data['entry_id']		= $entry_id;
$data['product_id']		= $v['product_id'];
$data['qty']			= $v['qty'];
$data['from_id']		= $id;
$model->add($data);
}
$data = array();
$data['id'] 			= $id;
$data['delivery_id'] 	= $delivery_id;
$data['entry_id'] 		= $entry_id;
$Allot->save($data);
}
public function delete()
{
parent::delete();
}
}

?>