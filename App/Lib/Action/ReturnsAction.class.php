<?php

class ReturnsAction extends WorkflowAction 
{
public function index()
{
$ret = $this->getFilter('Returns','A.');
$A = $this->prefix.'returns A ';
$B = $this->prefix.'salesorder B ';
$C = $this->prefix.'accounts C ';
$from 		= " FROM $A LEFT JOIN $B ON (A.order_id=B.id) LEFT JOIN $C ON (B.company_id=C.id)";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.* $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->setTitle('销售退货单列表');
$this->display();
}
public function add()
{
$order_id = $_REQUEST['order_id'];
$this->assign('order_id',$order_id);
$this->setTitle('新建销售退货');
$this->display();
}
public function insert()
{
$this->validate();
$_POST['re_delivery'] = isset($_REQUEST['re_delivery'])?1:0;
$ret = $this->_orderDetails();
$gp = array();
$sum = 0;
foreach ($_REQUEST['pid'] as $k=>$v)
{
$qty = floatval($_REQUEST['qty'][$k]);
if( $qty >0 &&$ret[$v]['qty'] >0)
{
if( !isset($ret[$v]) ||$ret[$v]['qty'] <$qty )
{
$this->ajaxReturn('',$ret[$v]['productname'].',错误的退货商品或退货数量',0);
}
$data = array();
$data['product_id'] 	= $v;
$data['qty'] 			= $qty;
$data['price']			= $ret[$v]['sale_price'] +$ret[$v]['other_price'];
$sum += $data['price'] * $data['qty'];
$gp[] = $data;
}
}
if (count($gp) == 0)
{
$this->ajaxReturn('','请填写明细。',0);
}
$_POST['uid'] = $this->uid;
$_POST['sum'] = $sum;
$order_id = $_POST['order_id'];
$vo = M('Returns')->where("order_id=$order_id")->select();
foreach ($vo as $v)
{
if($v['status'] != 5 &&$v['status'] != 6)
{
if(isset($_POST['id']))
{
if($v['id'] != $_POST['id'])
{
$this->ajaxReturn('','还有退货单是提交状态，请先取消才能完成退货。',0);
}
}else{
$this->ajaxReturn('','还有退货单是提交状态，请先取消才能完成退货。',0);
}
}
}
$SO = D('Salesorder');
$vo = $SO->getDelivery($order_id);
foreach ($vo as $v)
{
if ($v['status'] == 1){
$this->ajaxReturn('','还有发货单是提交状态，请先取消才能完成退货。',0);
}
}
$id = $this->save('Returns');
if ($id !== false) {
$model = M('ReturnsDetails');
$model->where("returns_id=$id")->delete();
foreach ($gp as &$v)
{
$v['returns_id'] = $id;
$model->add($v);
}
$recordModel = D('Record');
$recordModel->insert($id,isset($_REQUEST['id'])?'修改退货单。':'保存退货单。',false,$this->getActionName());
$recordModel->insert($order_id,isset($_REQUEST['id'])?"新建退货单$id":"修改退货单$id",false ,'Salesorder');
$SO->where(array('id'=>$order_id))->save(array('isReturn'=>1));
$result['id'] = $id;
$this->ajaxReturn($result,'',1);
}
}
public function update()
{
$this->insert();
}
public function _before_show()
{
parent::_before_show();
$id = intval($_REQUEST['id']);
$model = D('Returns');
$vo = $model->getPerm($id);
if($vo == false)
{
$this->error(L('NO_PERM_VISIT'));
}
$this->assign('vo',$vo['base']);
$details = $model->getDetails($id);
$this->assign('details',$details);
}
public function show()
{
$vo = $this->__get('vo');
$this->assign('title','销售退货-'.$vo['id'].' '.$vo['abstract']);
$this->display();
}
public function edit()
{
$this->setTitle('修改退货单');
$this->_before_show();
$vo = $this->__get('vo');
$order_id = $vo['order_id'];
$details = $this->__get('details');
$list = D('Salesorder')->getDetails($order_id);
foreach ($list as &$v)
{
foreach ($details as $val)
{
if($v['product_id'] == $val['product_id'])
{
$v['return_qty'] = $val['qty'];
break;
}
}
}
$this->assign('list',$list);
$this->display();
}
public function _before_approve()
{
$id = intval($_REQUEST['id']);
$vo = M('Returns')->where("id=$id")->find();
$order_id = $vo['order_id'];
$vo = M('Delivery')->where("type=1 AND from_id=$order_id AND status=1")->select();
if($vo){
$this->error('还有发货单是提交状态，请先取消才能完成退货。');
}
}
public function afterApprove($success)
{
if(!$success)return;
$id = intval($_REQUEST['id']);
$model = D('Returns');
$vo = $model->where("id=$id")->find();
$re_delivery = $vo['re_delivery'];
$data['id'] = $id;
$data['status'] = 5;
$model->save($data);
$order_id = $vo['order_id'];
$warehouse_id = $vo['warehouse_id'];
$db = M();
$A = $this->prefix.'salesorder_details A';
$C = $this->prefix.'returns_details C';
$details = $db->query("SELECT A.*,C.qty return_qty FROM $A
			LEFT JOIN $C ON (A.product_id=C.product_id) 
			WHERE A.order_id=$order_id AND C.returns_id=$id");
$SD = M('SalesorderDetails');
$sum = 0;
foreach ($details as $v){
if($v['return_qty'] >0){
if(($v['qty'] -$v['deli_qty']) <$v['return_qty']){
$tmp				= array();
$tmp['product_id']	= $v['product_id'];
$tmp['qty']			= $v['return_qty'] -($v['qty'] -$v['deli_qty']);
$entry[] = $tmp;
if ($re_delivery == 1)
{
$SD->where("order_id=$order_id AND product_id=".$v['product_id'])->save(array('deli_qty'=>$tmp['qty']));
}
}
$data			= array();
if ($re_delivery == 1)
{
if(($v['qty'] -$v['deli_qty']) <$v['return_qty'])
{
$data['qty']	= $v['deli_qty'];
}else{
$data['qty']	= $v['qty'] -$v['return_qty'];
}
}else{
$data['qty']	= $v['qty'] -$v['return_qty'];
}
$SD->where("order_id=$order_id AND product_id=".$v['product_id'])->save($data);
$sum += $v['return_qty']*($v['sale_price'] +$v['other_price']);
}
}
$recordModel = D('Record');
if(count($entry) >0)
{
$entry_id = D('Entry')->transfer($id,$warehouse_id,3,$entry);
$recordModel->insert($id,"审核后，生成退货入库$entry_id",false,$this->getActionName());
}
$recordModel->insert($order_id,"审核退货单$id",true,'Salesorder');
D('Salesorder')->reCalculate($order_id);
}
private function _orderDetails()
{
$id = intval($_REQUEST['order_id']);
$model = D('Salesorder');
$vo = $model->getPerm($id);
if( $vo )
{
$status = $vo['base']['status'];
if ($status != 3 &&$status != 5)
{
$this->assign('return_error_info',"销售订单($id)的状态不是已审核或者已完成，不允许再做退货单。");
return false;
}
$details = $model->getDetails($id);
$ret = array();
foreach ($details as &$v)
{
$product_id = $v['product_id'];
if($v['qty'] >0){
$ret[$product_id] = $v;
}
}
return $ret;
}
return false;
}
public function showOrderDetails()
{
$ret = $this->_orderDetails();
if($ret)
{
$this->assign('list',$ret);
}
$this->display('order_filter');
}
public function delete()
{
parent::delete();
}
}?>