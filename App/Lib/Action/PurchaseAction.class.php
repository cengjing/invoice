<?php

class PurchaseAction extends WorkflowAction  
{
public function index()
{
$this->setTitle('采购单列表');
$vars = $this->getFilterSql();
$this->_pageList ( $vars['countSQL'],$vars['pageSQL'],$vars['sum'] );
$this->display();
}
private function getFilterSql()
{
$ret = $this->getFilter('Purchase','A.');
$A = $this->prefix .'purchase A ';
$B = $this->prefix .'user B ';
$C = $this->prefix .'vender C ';
$from 		= " FROM $A
						LEFT JOIN $C ON A.vender_id=C.id";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$order 		= " ORDER BY ".($ret['order'] != ''?$ret['order']:'A.id DESC');
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.name uid_name,C.company $from
						LEFT JOIN $B ON A.uid=B.id
						$where $order";
$vars['countSQL'] 	= $countSQL;
$vars['pageSQL'] 	= $pageSQL;
$vars['sum'] 		= $ret['arr_sum'];
return $vars;
}
public function add()
{
$this->setTitle('新建采购');
$ret = D('Purchase')->calculateMrp();
$list = array();
foreach ($ret as $v){
$vender_id = $v['vender_id'];
if($vender_id >0 )
{
if( $v['pur_qty']>0 ){
if(!isset($list[$vender_id]))
{
$list[$vender_id]['vender_id'] = $vender_id;
$list[$vender_id]['company'] = getVenderName($vender_id);
$list[$vender_id]['qty'] = 1;
}
else
{
$list[$vender_id]['qty'] += 1;
}
}
}
}
$this->assign('list',$list);
$default['mrp'] = 1;
$this->assign('default',$default);
$this->display();
}
public function getMrpList()
{
if(!$this->isAjax())return;
$vender_id = $_REQUEST['vender_id'];
$this->assign('venderName',getVenderName($vender_id));
$ret = D('Purchase')->calculateMrp();
foreach ($ret as $v){
if($v['vender_id'] == $vender_id &&floatval($v['pur_qty'])>0){
foreach ($v['details'] as $val){
$val['productname'] = $v['productname'];
$val['catno'] = $v['catno'];
$val['mfr_part_no'] = $v['mfr_part_no'];
$val['description'] = $v['description'];
$val['ch_description'] = $v['ch_description'];
$val['purchase_price'] = $v['purchase_price'];
$list[] = $val;
}
}
}
$this->assign('list',$list);
$this->display('list');
}
public function insert()
{
$this->validate();
$model = D('Purchase');
$ret = $model->calculateMrp();
$_POST['uid'] = $this->uid;
$gp = array();
$de = array();
$sum = 0;
foreach ( $_REQUEST['mrp_pid'] as $k=>$v )
{
if(intval($_REQUEST['order_id'][$k]>0)){
$order_id = $_REQUEST['order_id'][$k];
}else{
$order_id = null;
}
$product_id = $v;
$qty = $_REQUEST['qty'][$k];
$purchase_price = $_REQUEST['purchase_price'][$k];
$sale_price = $_REQUEST['sale_price'][$k];
if(!isset($gp[$product_id]))
{
$gp[$product_id] = array(
'product_id'=>$product_id,
'qty'=>$qty,
'price'=>$purchase_price
);
}else {
$gp[$product_id]['qty'] += $qty;
}
$sum += $qty * $purchase_price;
$tmp = array();
$tmp['qty']				= $qty;
$tmp['purchase_price']	= $purchase_price;
$tmp['order_id']		= $order_id;
$tmp['sale_price']		= $sale_price;
$gp[$v]['details'][] = $tmp;
}
foreach ($_REQUEST['pid'] as $k=>$v)
{
$product_id = $v;
$qty = $_REQUEST['def_qty'][$k];
$purchase_price = $_REQUEST['def_purchase_price'][$k];
if(!isset($gp[$product_id]))
{
$gp[$product_id] = array(
'product_id'=>$product_id,
'qty'=>$qty,
'price'=>$purchase_price
);
}else {
$gp[$product_id]['qty'] += $qty;
}
$tmp = array();
$tmp['qty']				= $qty;
$tmp['purchase_price']	= $purchase_price;
$gp[$v]['details'][] = $tmp;
$sum += $qty * $purchase_price;
}
$_POST['sum'] = $sum;
$_POST['amount'] = $sum +floatval($_POST['other_fund']) +floatval($_POST['freight']);
if(count($gp) == 0) $this->ajaxReturn('','请要填写采购明细',0);
foreach ($gp as $k=>$v){
$product_id = $k;
foreach ($v['details'] as $de)
{
$order_id = $de['order_id'];
if($order_id != ''&&!isset($_REQUEST['id']))
{
if($de['qty'] != $ret[$product_id]['details'][$order_id]['pur_qty'])
{
$productSet = getProductInfo($product_id);
$this->ajaxReturn('',$order_id.'中'.$productSet['productname'].','.$productSet['catno'].',的采购数量不正确',0);
}
}
}
}
$id = $this->save('Purchase');
if ($id !== false) {
$PD = M('PurchaseDetails');
$POD = M('PurchaseOrderDetails');
$PD->where("purchase_id=$id")->delete();
$POD->where("purchase_id=$id")->delete();
$SO = M('Salesorder');
foreach ($gp as $v)
{
$data = array();
$data['purchase_id'] 	= $id;
$data['product_id'] 	= $v['product_id'];
$data['price'] 			= $v['price'];
$data['qty'] 			= $v['qty'];
$PD->add($data);
foreach ($v['details'] as $val)
{
$data = array();
$data['purchase_id'] 	= $id;
$data['product_id'] 	= $v['product_id'];
$data['qty'] 			= $val['qty'];
$data['purchase_price'] = floatval($val['purchase_price']);
if(isset($val['order_id']))
{
$SO->save(array('id'=>$val['order_id'],'isPurchase'=>1));
$data['order_id'] 		= $val['order_id'];
}
if(isset($val['sale_price']))
{
$data['sale_price']		= floatval($val['sale_price']);
}
$POD->add($data);
}
}
S('purchase_qty',null);
D('Record')->insert($id,(isset($_REQUEST['id'])?'修改':'保存').'采购订单',false,$this->getActionName());
$result['id'] = $id;
$this->ajaxReturn($result,'',1);
}else {
$this->ajaxReturn ('','新增失败!',0);
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
$model = D('Purchase');
$vo = $model->getInfo($id);
$this->assign('vo',$vo['base']);
$vo = $model->getDetails($id);
$this->assign('group',$vo['group']);
$this->assign('details',$vo['details']);
$this->assign('def',$vo['def']);
$this->assign('orders',$vo['orders']);
}
public function show()
{
$vo = $this->__get('vo');
$this->setTitle('采购单_'.$vo['id'].'_'.$vo['abstract']);
$this->display();
}
public function edit() 
{
$this->_before_show();
$vo = $this->__get('vo');
$this->setTitle('编辑采购单_'.$vo['id']);
$this->display ();
}
public function clearChache()
{
S('purchase_qty',NULL);
$this->redirect('purchase/add');
}
public function afterApprove($success)
{
$id = intval($_REQUEST['id']);
$model = D('Purchase');
$vo = $model->getDetails($id);
$orders = array();
foreach ($vo['orders'] as $order){
if (!in_array($order['order_id'],$orders)){
array_push($orders,$order['order_id']);
}
}
$model = D('Record');
if($success){
foreach ($orders as $order_id){
if(intval($order_id)>0)
$model->insert($order_id,"已生成销售采购单：$id",true ,'Salesorder');
}
}
}
public function delete()
{
$id = intval($_REQUEST['id']);
D('Record')->insert($id,'删除采购订单',false,$this->getActionName());
$this->_delete();
}
public function advShow()
{
$id = intval($_REQUEST['id']);
$model = D('Purchase');
$model = M();
$this->assign('title','采购订单-'.$id.'-更多信息');
$this->assign('id',$id);
$A = $this->prefix.'arrival_details A';
$B = $this->prefix.'arrival B';
$sql = "SELECT B.* FROM $A LEFT JOIN $B ON (A.arrival_id=B.id) WHERE A.purchase_id=$id GROUP BY B.id ORDER BY B.id DESC";
$vo = $model->query($sql);
if(count($vo)==0)
$vo = null;
$this->assign('arrival',$vo);
$A = $this->prefix.'purchasereturn A';
$sql = "SELECT * FROM $A WHERE A.purchase_id=$id";
$vo = $model->query($sql);
if(count($vo)==0)
$vo = null;
$this->assign('returns',$vo);
$A = $this->prefix.'purchaseinvoice_details A';
$B = $this->prefix.'purchaseinvoice B';
$sql = "SELECT B.* FROM $A LEFT JOIN $B ON (A.pid=B.id) WHERE A.purchase_id=$id GROUP BY B.id ORDER BY B.id DESC";
$vo = $model->query($sql);
if(count($vo)==0)
$vo = null;
$this->assign('invoice',$vo);
$this->display('adv');
}
public function export()
{
$vars = $this->getFilterSql();
$pageSQL = $vars['pageSQL'];
$vo = M()->query($pageSQL);
$this->assign('vo',$vo);
$this->display('public:export');
}
}?>