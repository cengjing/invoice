<?php

class ArrivalAction extends WorkflowAction
{
public function index()
{
$ret = $this->getFilter('Arrival','A.');
$A = $this->prefix .'arrival A ';
$B = $this->prefix .'user B ';
$C = $this->prefix .'warehouse_name C ';
$D = $this->prefix .'vender D ';
$from 		= " FROM $A ";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.name uid_name,C.name,D.company $from 
						LEFT JOIN $B ON A.uid=B.id 
						LEFT JOIN $C ON A.warehouse_id=C.id 
						LEFT JOIN $D ON A.vender_id=D.id
						$where ORDER BY A.cTime DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->setTitle('采购到货单列表');
$this->display();
}
public function add()
{
$this->setTitle('新建采购到货单');
$A = $this->prefix.'purchase A';
$B = $this->prefix.'purchase_details B';
$C = $this->prefix.'vender C';
$Sql = "SELECT SUM(B.qty-B.arr_qty) sum,C.company,C.id
				 FROM $B LEFT JOIN $A ON (A.id=B.purchase_id) LEFT JOIN $C ON(A.vender_id=C.id) 
				 WHERE A.status=3 AND B.arr_qty<B.qty GROUP BY A.vender_id";
$vo = M()->query($Sql);
$this->assign('list',$vo);
$this->assign('purchase_id',$_REQUEST['id']);
$this->display();
}
public function edit()
{
$this->_before_show();
$vo = $this->__get('vo');
$this->setTitle('修改采购到货单-'.$vo['id']);
$this->display();
}
public function insert()
{
$this->validate();
$purchase_id = intval($_REQUEST['pur_id']);
if($purchase_id >0){
$vo = M('Purchase')->where(array('id'=>$purchase_id))->find();
$_POST['vender_id'] = $vo['vender_id'];
}
$ret = $this->_purchaseDetails();
$gp = array();
$sum = 0;
foreach ($_REQUEST['pid'] as $k=>$v)
{
$qty = floatval($_REQUEST['qty'][$k]);
$pid = $v;
$purchase_id = $_REQUEST['purchase_id'][$k];
if($qty >0)
{
foreach ($ret as $val)
{
if ($val['purchase_id'] == $purchase_id &&$val['product_id'] == $pid)
{
if($val['qty']-$val['arr_qty']<$qty)
{
$this->ajaxReturn('',$val['productname'].'的到货数量超出上限！',0);
}
$data = array();
$data['product_id'] 	= $pid;
$data['qty'] 			= $qty;
$data['purchase_id']	= $purchase_id;
$gp[] = $data;
$sum += $qty * $val['price'];
break;
}
}
}
}
if (count($gp) == 0)
{
if(!isset($_POST['id']))
{
$this->ajaxReturn('','请填写明细。',0);
}
}
$id = intval($_POST['id']);
$AD = M('ArrivalDetails');
$PD = M('PurchaseDetails');
if($id>0){
$arrivalDetails = $AD->where("arrival_id=$id")->select();
foreach ($arrivalDetails as $v)
{
$purchase_id = $v['purchase_id'];
$product_id = $v['product_id'];
$vo = $PD->where("purchase_id='$purchase_id' AND product_id='$product_id'")->find();
$tmp += $v['qty'] * $vo['price'];
}
}
$_POST['uid'] = $this->uid;
$_POST['sum'] = $sum +$tmp;
$model = D('Arrival');
if (false === $model->create ()) {
$this->ajaxReturn ('',$model->getError (),0 );
}
if($id)	{
$model->save ();
}
else {
$id = $model->add ();
$id = $model->saveId($id);
}
if ($id !== false) 
{
foreach ($gp as &$v)
{
$purchase_id = $v['purchase_id'];
$arr[] = $purchase_id;
$product_id = $v['product_id'];
$qty = $v['qty'];
$flag = 0;
foreach ($arrivalDetails as $val)
{
if ($val['purchase_id'] == $purchase_id &&$val['product_id'] == $product_id)
{
$AD->where(array('arrival_id'=>$id,'product_id'=>$product_id))->setInc('qty',$qty);
$flag = 1;
}
}
if($flag == 0)
{
$v['arrival_id'] = $id;
$AD->add($v);
}
$PD->where(array('purchase_id'=>$purchase_id,'product_id'=>$product_id))->setInc('arr_qty',$qty);
}
$arr = array_unique($arr);
$Record = D('Record');
foreach ($arr as $v)
{
$Record->insert($v,'新建采购到货'.$id,false,'Purchase');
}
$result['id'] = $id;
$Record->insert($id,isset($_REQUEST['id'])?'修改到货单。':'新建到货单。',false,$this->getActionName());
$this->ajaxReturn($result,'',1);
}else {
$this->ajaxReturn ('','新增失败!',0 );
}
}
public function update()
{
$this->insert();
}
public function show()
{
$vo = $this->__get('vo');
$this->setTitle('采购到货单-'.$vo['id'].'-'.$vo['abstract']);
$this->display();
}
public function _before_show()
{
parent::_before_show();
$id = intval($_REQUEST['id']);
$model = D('Arrival');
$vo = $model->getInfo($id);
$this->assign('vo',$vo['base']);
$details = $model->getDetails($id);
$this->assign('details',$details);
}
public function delete()
{
$id = intval($_REQUEST['id']);
$vo = M('ArrivalDetails')->where("arrival_id=$id")->select();
$items = '';
foreach ($vo as $v)
{
$items[] = $v['id'];
}
$this->_deleteItems($items);
parent::delete();
$this->redirect("arrival/show",array('id'=>$id));
}
public function deleteItem()
{
if(!$this->isAjax())return;
$items = explode(',',$_REQUEST['items']);
$arrival_id = $this->_deleteItems($items);
$result['id'] = $arrival_id;
$this->ajaxReturn($result,'',1);
}
private function _deleteItems($items)
{
$AD = M('ArrivalDetails');
$PD = M('PurchaseDetails');
$Record = D('Record');
$model = M('arrival');
foreach ($items as $val)
{
if($val != '')
{
$vo = $AD->where("id=$val")->find();
$purchase_id 	= $vo['purchase_id'];
$product_id 	= $vo['product_id'];
$qty 			= $vo['qty'];
$arrival_id		= $vo['arrival_id'];
$AD->where("id=$val")->delete();
$PD->where(array('purchase_id'=>$purchase_id,'product_id'=>$product_id))->setDec('arr_qty',$qty);
$p = getProductInfo($product_id);
$Record->insert($arrival_id,'删除到货商品：'.$p['productname'].'['.$p['catno'].']',false,$this->getActionName());
$A = $this->prefix.'purchase_details A';
$B = $this->prefix.'arrival_details B';
$SQL = "SELECT SUM(B.qty*A.price) AS sum FROM $A,$B WHERE B.arrival_id=$arrival_id AND A.purchase_id=B.purchase_id AND A.product_id=B.product_id";
$vo = M()->query($SQL);
$details = $AD->where("arrival_id=$arrival_id")->select();
$data = array();
$data['sum'] 	= ($vo == null) ?0 : $vo[0]['sum'];
$data['id'] 	= $arrival_id;
if(null == $details)
{
$data['status'] = 6;
}
$model->save($data);
}
}
return $arrival_id;
}
public function showArrivalDetails()
{
if(!$this->isAjax())return;
$ret = $this->_purchaseDetails();
$purchase_id = intval($_REQUEST['pur_id']);
$vender_id = intval($_REQUEST['vender_id']);
if( $purchase_id >0)
{
$purchase_id = $_REQUEST['pur_id'];
$vo = M('Purchase')->where(array('id'=>$purchase_id))->find();
$this->assign('venderName',getVenderName($vo['vender_id']));
foreach ($ret as $v){
if($v['purchase_id'] == $purchase_id){
$list[] = $v;
}
}
}else{
$this->assign('venderName',getVenderName($vender_id));
foreach ($ret as $v){
if($v['vender_id'] == $vender_id){
$list[] = $v;
}
}
}
if(count($list)>0)
{
$this->assign('list',$list);
}
$this->display('purchase_filter');
}
private function _purchaseDetails()
{
$A = $this->prefix.'purchase A';
$B = $this->prefix.'purchase_details B';
$C = $this->prefix.'products C';
$Sql = "SELECT B.*,C.productname,C.catno,C.spec,C.unit,C.description,C.ch_description,C.mfr_part_no,C.vender_id
				 FROM $B LEFT JOIN $A ON (A.id=B.purchase_id) LEFT JOIN $C ON(B.product_id=C.id) 
				 WHERE A.status=3 AND B.arr_qty<B.qty ORDER BY B.purchase_id ASC,C.catno ASC";
return M()->query($Sql);
}
public function afterApprove($success)
{
$id = intval($_REQUEST['id']);
if ($success)
{
$vo = M('Arrival')->where("id=$id")->find();
$warehouse_id = $vo['warehouse_id'];
$vo = M('ArrivalDetails')->query("SELECT product_id,sum(qty) qty FROM __TABLE__ WHERE arrival_id=$id GROUP BY product_id");
D('Entry')->transfer($id,$warehouse_id,1,$vo);
}
}
}
?>