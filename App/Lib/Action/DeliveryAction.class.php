<?php

class DeliveryAction extends GlobalAction 
{
public function index()
{
$this->setTitle('销售发货列表');
$warehouse = D('Warehouse')->getSaleWarehouse();
$ws = '';
foreach ($warehouse as $val){
$ws .= (($ws == '') ?'': ',') .$val['id'];
}
$ret = $this->getFilter('Delivery','A.');
$perm = $ret['perm'];
$assignto = '';
if(!$perm['all']){
foreach ($perm['users'] as $v){
$assignto .= (($assignto == '')?'':',').$v['id'];
}
if($assignto != '') $assignto = " AND C.assignto IN ($assignto)";
}
$assignto='';
$A = $this->prefix.'delivery A';
$B = $this->prefix.'contacts B';
$C = $this->prefix.'accounts C';
$from 		= " FROM $A LEFT JOIN $B ON (A.contact_id=B.id) LEFT JOIN $C ON (B.company_id=C.id)";
$where 		= " WHERE A.warehouse_id IN ($ws) $assignto ".($ret['where'] != ''?" AND ".$ret['where']:'');
$countSql 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSql 	= " SELECT A.* $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSql,$pageSql,$ret['arr_sum']);
$this->display();
}
public function add()
{
$this->setTitle('新建销售发货单');
$order_id = intval($_REQUEST['order_id']);
$vo = D('Salesorder')->getPerm($order_id);
$this->assign('data',$vo['base']);
$c['company'] = $vo['account']['company'];
$c['name'] = $vo['contact']['name'];
$c['address'] = empty($vo['contact']['address'])?$vo['account']['address']:$vo['contact']['address'];
$c['phone'] = empty($vo['contact']['phone'])?$vo['account']['phone']:$vo['contact']['phone'];
$c['regioncode'] = empty($vo['contact']['regioncode'])?$vo['account']['regioncode']:$vo['contact']['regioncode'];
$c['postalcode'] = empty($vo['contact']['postalcode'])?$vo['account']['postalcode']:$vo['contact']['postalcode'];
$c['mobilephone'] = $vo['contact']['mobilephone'];
$this->assign('contact',$c);
$this->display();
}
public function insert()
{
$this->validate();
$ret = $this->_orderDetails();
$_POST['uid']	 = $this->uid;
$_POST['type']	 = 1;
$gp = array();
$all_order = array();
foreach ( $_REQUEST['product_id'] as $k=>$v )
{
$qty = floatval($_REQUEST['qty'][$k]);
if( $qty >0 )
{
$order_id = $_REQUEST['order_id'][$k];
$all_order[] = $order_id;
if(!isset($gp[$v]))
{
$gp[$v]['product_id'] = $v;
$gp[$v]['qty'] = $qty;
}
else
{
$gp[$v]['qty'] += $_REQUEST['qty'][$k];
}
$gp[$v]['details'][$order_id] = array(
'product_id'=>$v,
'order_id'=>$order_id,
'qty'=>$qty
);
}
}
$all_order = array_unique($all_order);
if(count($gp) == 0) $this->ajaxReturn('','请要填写发货明细',0);
$data = array();
foreach ($gp as $k=>$v){
$product_id = $v['product_id'];
if($gp[$product_id]['qty'] >$ret['stockDetails'][$product_id])
{
$productSet = getProductInfo($product_id);
$this->ajaxReturn('',$productSet['productname'].','.$productSet['catno'].',的发货数量不能大于库存数量',0);
}
else
{
$productInfo = $gp[$product_id]['details'];
foreach ($productInfo as $oid=>$val)
{
if($val['qty'] >$productInfo[$oid]['qty'] -$productInfo[$oid]['deli_qty'])
{
$productSet = getProductInfo($product_id);
$this->ajaxReturn('',$productSet['productname'].','.$productSet['catno'].',的发货数量不能大于订单中订货数量',0);
}
else
{
$data[]	= $val;
}
}
}
}
$warehouse_id = $_POST['warehouse_id'];
$warehouse = getWarehouseInfo($warehouse_id);
$dbfield = $warehouse['dbfield'];
$model = M('DeliveryDetails');
$model->startTrans();
$id = $this->_save();
if ($id !== false)
{
$model->where(array('delivery_id'=>$id))->delete();
$vo = array();
$m = D('Warehouse');
foreach ($data as $v)
{
$A = $this->prefix.'salesorder_details';
$SQL = sprintf("UPDATE $A SET deli_qty=deli_qty+%s 
								WHERE order_id=%s AND product_id=%s",
$v['qty'],$v['order_id'],$v['product_id']);
$ret = $m->execute($SQL);
if($ret == false)
{
$model->rollback();
$this->ajaxReturn ('','修改订单可发货数量失败!',0);
}
$warehouseQty = $m->Dec($dbfield,$v['product_id'],$v['qty']);
if( $warehouseQty == false)
{
$model->rollback();
$this->ajaxReturn ('','修改库存数量失败!',0);
}
$vo = array();
$vo['delivery_id']	=	$id;
$vo['product_id']	=	$v['product_id'];
$vo['order_id']		=	$v['order_id'];
$vo['qty']			=	$v['qty'];
$vo['stock_qty']	=	$warehouseQty;
$ret = $model->add($vo);
if($ret == false)
{
$model->rollback();
$this->ajaxReturn ('','记录发货单明细失败!',0);
}
}
$m = D('Salesorder');
$recordModel = D('Record');
foreach ($all_order as $order_id)
{
$recordModel->insert($order_id,"新建发货单。发货单号：$id",false ,'Salesorder');
$m->autoCheck($order_id);
}
if($model->commit())
{
$data = array();
$data['id'] = $id;
$this->ajaxReturn($data,'',1);
}
}else {
$this->ajaxReturn ('','新增失败!',0 );
}
}
private function _save()
{
$model = D ('Delivery');
if (false === $model->create ()) {
$this->ajaxReturn ('',$model->getError (),0 );
}
if(isset($_REQUEST['id'])){
$id = $_REQUEST['id'];
$vo = $model->where("id=$id AND status=1")->find();
if(false === $vo){
$this->ajaxReturn ('','当前状态不是已保存，不能再修改！',0 );
}
$model->save ();
}else{
$id = $model->add ();
if(intval($id)<100000000){
$new_id = intval($id)+100000000;
$model->execute("UPDATE __TABLE__ SET id=$new_id WHERE id=$id");
$id = $new_id;
}
}
return $id;
}
public function _before_show()
{
$id = intval($_REQUEST['id']);
$model = D('Delivery');
$vo = $model->getPerm($id);
if($vo === null)
{
$this->error('数据库中并没有这张单据。');
}
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->assign('vo',$vo['base']);
$this->assign('details',$model->getDetails($id));
}
public function show()
{
$vo = $this->__get('vo');
$this->setTitle('发货单_'.$vo['id']);
$this->display();
}
public function delete()
{
$id = $_REQUEST['id'];
$model = D('Delivery');
$vo = $model->getPerm($id);
if($vo === null)
{
$this->error('数据库中并没有这张单据。');
}
if($vo === false)
{
$this->error('您没有删除这张订单的权限。');
}
$warehouse_id = $vo['base']['warehouse_id'];
$warehouse = getWarehouseInfo($warehouse_id);
$warehouseField = $warehouse['dbfield'];
if($vo['base']['status'] != 1)
{
$this->error('删除发货单失败，发货单状态已经改变。');
}
$model->startTrans();
$data['status'] 	= 3;
$data['act_uid'] 	= $this->uid;
$data['act_time'] 	= time();
$ret = $model->where("id=$id AND status=1")->save($data);
if($ret === false)
{
$model->rollback();
$this->error ('修改发货单为删除状态失败!');
}
$m = M('SalesorderDetails');
$w = D('Warehouse');
$DD = M('DeliveryDetails');
$orders = array();
$details = $model->getDetails($id);
foreach ($details as $v)
{
$order_id = $v['order_id'];
$qty = $v['qty'];
$product_id = $v['product_id'];
$ret = $m->where(array('order_id'=>$order_id,'product_id'=>$product_id))->setDec('deli_qty',$qty);
if($ret === false)
{
$model->rollback();
$this->error ('修改发货单为删除状态失败!');
}
$stockQty = $w->Inc($warehouseField,$product_id,$qty);
if( $stockQty === false)
{
$model->rollback();
$this->error ('修改库存数量失败!');
}
$data 				= array();
$data['id']			= $v['id'];
$data['stock_qty']	= $stockQty;
$ret = $DD->save($data);
if($ret === false)
{
$model->rollback();
$this->error ('保存库存数量失败!');
}
$orders[] = $order_id;
}
$orders = array_unique($orders);
$m = D('Salesorder');
$recordModel = D('Record');
foreach ($orders as $order_id)
{
$recordModel->insert($order_id,"删除发货单。发货单号：$id",false ,'Salesorder');
$m->autoCheck($order_id);
}
if($model->commit())
{
$this->success('成功删除发货单。');
}
}
private function _orderDetails()
{
$id = intval($_REQUEST['contact_id']);
$wid = intval($_REQUEST['warehouse_id']);
if( D('Contacts')->getPerm($id) )
{
$w = M('WarehouseName')->where("id=$wid")->find();
$dbfield =$w['dbfield'];
$this->assign('warehouseName',$w['name']);
$A = $this->prefix.'salesorder_details A';
$B = $this->prefix.'salesorder B';
$C = $this->prefix.'products C';
$D = $this->prefix.'warehouse D';
$SQL = "SELECT A.*,C.productname,C.catno,C.description,C.ch_description,B.cTime,B.abstract,C.unit,C.spec,A.qty-A.deli_qty max,D.$dbfield FROM $A 
					LEFT JOIN $B ON (A.order_id=B.id) 
					LEFT JOIN $C ON (C.id=A.product_id)
					LEFT JOIN $D ON (D.product_id=A.product_id)
					WHERE B.contact_id=$id AND A.deli_qty<A.qty AND B.status=3 ORDER BY C.catno ASC,B.id ASC";
$vo = M()->query($SQL);
foreach ($vo as &$v)
{
$v['max_qty'] = number_format($v['qty'] -$v['deli_qty'],2,".","");
$v['stock'] = ($v[$dbfield]=='') ?'0.00': $v[$dbfield];
$v['qty'] = number_format($v['qty'],2,".","");
$stockDetails[$v['product_id']] = $v[$dbfield];
}
$ret['details'] = $vo;
$ret['stockDetails'] = $stockDetails;
return $ret;
}
return false;
}
public function showOrderDetails()
{
$ret = $this->_orderDetails();
if($ret)
{
$this->assign('list',$ret['details']);
}
$this->display('order_filter');
}
Public function back()
{
$id = intval($_REQUEST['id']);
$model = D('Delivery');
$vo = $model->getPerm($id);
if($vo === null)
{
$this->error('数据库中并没有这张单据。');
}
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
if($vo['base']['status'] != 2)
{
$this->error('单据的状态不是已发货，不能被退回。');
}
$map['id'] = $id;
$map['status'] = 4;
$ret = $model->save($map);
if($ret == false)
{
$this->error ('修改状态失败!');
}
$this->success('提交到发货的库管。');
}
}
?>