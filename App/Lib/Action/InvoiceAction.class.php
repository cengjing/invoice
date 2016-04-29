<?php

class InvoiceAction extends GlobalAction 
{
public function index()
{
$this->assign('title','销售发票列表');
$ret = $this->getFilter('Invoice','A.');
$sumCount = $ret['sum'];
$A = $this->prefix .'invoice A ';
$B = $this->prefix .'contacts B ';
$C = $this->prefix .'accounts C ';
$from 		= " FROM $A 
						LEFT JOIN $B ON A.contact_id=B.id 
						LEFT JOIN $C ON B.company_id=C.id";
$where 		= " WHERE A.in_type=1 ".($ret['where'] != ''?" AND ".$ret['where']:'');
$countSQL 	= " SELECT COUNT(*) $sumCount $from $where ";
$pageSQL 	= " SELECT A.*,B.name contact,C.company $from $where ORDER BY A.cTime DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->display();
}
public function add()
{
$this->assign('title','新建销售发票');
$order_id = intval($_REQUEST['order_id']);
$delivery_id = intval($_REQUEST['delivery_id']);
if ($order_id>0){
$vo = D('Salesorder')->getPerm($order_id);
$this->assign('data',$vo['base']);
$this->assign('contact',$vo['contact']);
$this->assign('order_id',$order_id);
}else if ($delivery_id>0){
$vo = D('Delivery')->getInfo($delivery_id);
$data['company'] = $vo['company']['company'];
$data['company_id'] = $vo['company']['id'];
$data['contact'] = $vo['contact']['name'];
$data['contact_id'] = $vo['contact']['id'];
$this->assign('data',$data);
$this->assign('contact',$vo['contact']);
$this->assign('delivery_id',$delivery_id);
}
$this->display();
}
public function insert()
{
$this->validate();
$ret = $this->_orderDetails();
$contact_id = $_REQUEST['contact_id'];
$vo = D('Contacts')->getPerm($contact_id);
if($vo === null)
{
$this->ajaxReturn('','数据库中并没有这个客户。',0);
}
if($vo == false)
{
$this->ajaxReturn('','您没有这个客户的操作权限。',0);
}
$_POST['assignto'] = $vo['account']['assignto'];
$_POST['uid'] = $this->uid;
$gp = array();
$static = array();
$amount = 0;
foreach ( $_REQUEST['productname'] as $k=>$v )
{
$qty = floatval($_REQUEST['qty'][$k]);
if( $qty >0 )
{
$order_id = $_REQUEST['order_id'][$k];
$data = array();
$data['productname']	= $v;
$data['product_id']		= $_REQUEST['product_id'][$k];
$data['qty']			= $qty;
$data['order_id']		= $order_id;
$data['sale_price']		= floatval($_REQUEST['price'][$k]);
$data['sum_price']		= number_format($data['sale_price']*$qty,2,".","");
$data['unit']			= $_REQUEST['unit'][$k];
$data['spec']			= $_REQUEST['spec'][$k];
$gp[] = $data;
$amount += $data['sum_price'];
if(!isset($static[$order_id]))
{
$static[$order_id] = $data['sum_price'];
}
else
{
$static[$order_id] += $data['sum_price'];
}
}
}
if(count($gp) == 0 ||count($static) == 0)
{
$this->ajaxReturn('','请填写发票明细。',0);
}
foreach ($static as $k=>$v)
{
if ( $ret[$k]['total']-$ret[$k]['invoice']<$v)
{
$this->ajaxReturn('',"销售订单：$k 的开票金额超出上限。",0);
}
}
$_POST['apply_uid'] 	= $this->uid;
$_POST['amount']		= $amount;
$_POST['in_type']		= 1;
$M = M();
$M->startTrans();
$model = D('Invoice');
if (false === $model->create ()) {
$this->ajaxReturn ('',$model->getError (),0 );
}
$id = $model->add ();
if(intval($id)<100000000){
$new_id = intval($id)+100000000;
$model->execute("UPDATE __TABLE__ SET id=$new_id WHERE id=$id");
$id = $new_id;
}
if ($id !== false)
{
$model = M('InvoiceDetails');
foreach ($gp as &$v)
{
$v['invoice_id'] = $id;
$ret = $model->add($v);
if($ret == false)
{
$M->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
}
$model = M('InvoiceOrder');
$order = D('Salesorder');
foreach ($static as $k=>$v){
$data = array();
$data['invoice_id'] = $id;
$data['order_id'] = $k;
$data['amount'] = $v;
$ret = $model->add($data);
if($ret == false)
{
$M->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
D('Record')->insert($k,"蓝字发票申请：".$id."，金额：".formatCurrency($v),false,'Salesorder');
$ret = $order->execute("UPDATE __TABLE__ SET invoice=invoice+$v WHERE id=$k");
if($ret == false)
{
$M->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
$order->autoCheck($k);
}
$result['id'] = $id;
if($M->commit())
{
$this->ajaxReturn($result,'',1);
}
}else {
$this->ajaxReturn ('','新增失败!',0);
}
}
public function show()
{
$id = intval($_REQUEST['id']);
$model = D('Invoice');
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
$details = $model->getDetails($id);
$this->assign('details',$details);
$this->assign('title',$vo['base']['id'].'_销售发票');
$this->display();
}
private function _orderDetails()
{
$id = intval($_REQUEST['contact_id']);
if( !D('Contacts')->getPerm($id) )return ;
$oid = $_REQUEST['from_order_id'];
$did = $_REQUEST['delivery_id'];
$A = $this->prefix.'salesorder_details A';
$B = $this->prefix.'salesorder B';
$C = $this->prefix.'products C';
if(!empty($did)){
$D = $this->prefix.'delivery_details D';
$SQL = "SELECT A.*,C.productname,C.catno,C.ch_description,C.description,B.cTime,B.abstract,B.total,B.invoice,C.unit,C.spec FROM $D 
					LEFT JOIN $A ON (D.product_id=A.product_id AND D.order_id=A.order_id)
					LEFT JOIN $B ON (A.order_id=B.id)
					LEFT JOIN $C ON (C.id=A.product_id)
					WHERE D.delivery_id=$did AND B.invoice<B.total AND B.status=3 ORDER BY C.catno ASC";
}else{
$SQL = "SELECT A.*,C.productname,C.catno,C.ch_description,C.description,B.cTime,B.abstract,B.total,B.invoice,C.unit,C.spec FROM $A 
					LEFT JOIN $B ON (A.order_id=B.id) 
					LEFT JOIN $C ON (C.id=A.product_id)
					WHERE B.contact_id=$id AND B.invoice<B.total AND B.status=3 ORDER BY C.catno ASC";
}
$vo = M()->query($SQL);
$ret = array();
foreach ($vo as $v)
{
$product_id = $v['product_id'];
$order_id = $v['order_id'];
if (!isset($ret[$order_id]))
{
$ret[$order_id]['order_id']		= $v['order_id'];
$ret[$order_id]['cTime'] 		= getTime($v['cTime']);
$ret[$order_id]['total'] 		= $v['total'];
$ret[$order_id]['invoice'] 		= $v['invoice'];
$ret[$order_id]['amount'] 		= $v['total'] -$v['invoice'];
$ret[$order_id]['abstract'] 	= $v['abstract'];
}
$v['sale_price'] = number_format($v['sale_price'] +$v['other_price'],2,".","");
$ret[$order_id]['details'][$product_id] = $v;
}
return $ret;
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
}?>