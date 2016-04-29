<?php

class RedinvoiceAction extends GlobalAction
{
public function index()
{
$this->assign('title','销售发票列表');
$ret = $this->getFilter('Invoicemanage','A.');
$sumCount = $ret['sum'];
$A = $this->prefix .'invoice A ';
$B = $this->prefix .'contacts B ';
$C = $this->prefix .'accounts C ';
$from 		= " FROM $A
						LEFT JOIN $B ON A.contact_id=B.id
						LEFT JOIN $C ON B.company_id=C.id";
$where 		= " WHERE A.in_type=2 ".($ret['where'] != ''?" AND ".$ret['where']:'');
$countSQL 	= " SELECT COUNT(*) $sumCount $from $where ";
$pageSQL 	= " SELECT A.*,B.name contact,C.company $from $where ORDER BY A.cTime DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->display();
}
public function add()
{
$this->assign('title','新建红字销售发票');
$this->display();
}
private function _details()
{
$oid = $_REQUEST['from_order_id'];
$iid = $_REQUEST['invoice_id'];
if(!empty($oid)){
$model = D('Salesorder');
$vo = $model->getPerm($oid);
$details = $model->getDetails($oid);
if($vo === null)
{
$this->ajaxReturn ('','数据库中并没有销售订单:'.$oid,0);
}
if($vo == false)
{
$this->ajaxReturn ('','您没有查看销售订单:'.$oid.'的权限。',0);
}
}else{
$model = D('Invoice');
$vo = $model->getPerm($iid);
$details = $model->getDetails($iid);
if($vo === null)
{
$this->ajaxReturn ('','数据库中并没有蓝字发票:'.$iid,0);
}
if( $vo['base']['in_type'] != 1)
$this->ajaxReturn ('','发票:'.$iid.'不是蓝字发票，不能开红字发票',0);
if( $vo['base']['status'] !=2 &&$vo['base']['status'] !=4 )
$this->ajaxReturn ('','发票:'.$iid.'状态不正确，不能再开红字发票',0);
if($vo == false)
{
$this->ajaxReturn ('','您没有查看蓝字发票:'.$iid.'的权限。',0);
}
}
$contact_id = $vo['contact']['id'];
$assignto = $vo['account']['assignto'];
$ret['contact_id'] = $contact_id;
$ret['assignto'] = $assignto;
$ret['details'] = $details;
return $ret;
}
public function showDetails()
{
$ret = $this->_details();
$this->assign('details',$ret['details']);
$this->display('order_filter');
}
public function insert()
{
$this->validate();
$ret = $this->_details();
$contact_id = $ret['contact_id'];
$assignto = $ret['assignto'];
$_POST['assignto'] 		= $assignto;
$_POST['contact_id'] 	= $contact_id;
$_POST['uid'] 			= $this->uid;
$_POST['in_type']		= 2;
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
$this->ajaxReturn('','请填写订单明细。',0);
}
$model = M('Salesorder');
foreach ($static as $k=>$v)
{
$vo = $model->where(array('id'=>$k))->find();
if($vo == null)
$this->ajaxReturn('','销售订单:'.$k.'不存在。',0);
if($v >$vo['invoice'])
{
$this->ajaxReturn('','销售订单:'.$k.'已开发票金额:'.$vo['invoice'].'不能小于红字发票金额:'.$v,0);
}
}
$_POST['apply_uid'] = $this->uid;
$_POST['amount']		= $amount;
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
D('Record')->insert($k,"红字发票申请：".$id."，金额：".formatCurrency($v),false,'Salesorder');
$ret = $order->execute("UPDATE __TABLE__ SET invoice=invoice-$v WHERE id=$k");
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
}
?>