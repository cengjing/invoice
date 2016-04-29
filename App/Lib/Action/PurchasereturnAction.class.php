<?php

class PurchasereturnAction extends WorkflowAction 
{
public function index()
{
$this->setTitle('采购退货');
$ret = $this->getFilter('PurchaseReturn','A.');
$A = $this->prefix .'purchasereturn A ';
$B = $this->prefix .'user B ';
$C = $this->prefix .'vender C ';
$from 		= " FROM $A 
						LEFT JOIN $C ON A.vender_id=C.id";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.name uid_name,C.company $from
						LEFT JOIN $B ON A.uid=B.id
						$where ORDER BY A.cTime DESC";
$this->_pageList ($countSQL,$pageSQL,$ret['arr_sum']);
$this->display();
}
public function add()
{
$id = $_REQUEST['id'];
$this->assign('purchase_id',$id);
$this->setTitle('新建采购退货单');
$this->display();
}
public function showPurchaseDetails()
{
if(!$this->isAjax())return;
$pid = intval($_REQUEST['purchase_id']);
$vo = D('Purchase')->getDetails($pid);
$this->assign('list',$vo['details']);
$this->display('purchase_details');
}
public function insert()
{
$this->validate();
$purchase_id = intval($_REQUEST['purchase_id']);
$gp = array();
$sum = 0;
$model = D('Purchase');
$vo = $model->getInfo($purchase_id);
$_POST['vender_id'] = $vo['base']['vender_id'];
$vo = $model->getDetails($purchase_id);
foreach ( $_REQUEST['product_id'] as $k=>$v )
{
$pid = $v;
$qty = floatval($_REQUEST['qty'][$k]);
foreach ($vo['details'] as $val)
{
if($val['product_id'] == $pid)
{
if($qty >0)
{
if($qty >$val['qty'] -$val['arr_qty'])
{
$productSet = getProductInfo($pid);
$this->ajaxReturn('',$productSet['productname'].','.$productSet['catno'].',的退货数量不正确',0);
}
$tmp = array();
$tmp['qty']	= $qty;
$tmp['product_id']	= $pid;
$sum += $qty * $val['price'];
$gp[] = $tmp;
break;
}
}
}
}
if(count($gp) == 0) $this->ajaxReturn('','请填写退货明细',0);
$_POST['uid'] = $this->uid;
$_POST['amount'] = $sum;
$model = D('Purchasereturn');
$vo = $model->where("purchase_id=$purchase_id")->select();
foreach ($vo as $v)
{
if($v['status'] != 5 &&$v['status'] != 6)
{
if(isset($_POST['id']))
{
if($v['id'] != $_POST['id'])
{
$this->ajaxReturn('','还有未完成的采购退货单，不能再新建。',0);
}
}else{
$this->ajaxReturn('','还有未完成的采购退货单，不能再新建。',0);
}
}
}
$id = $model->saveInfo();
$Record = D('Record');
if ($id !== false) {
$Record->insert($purchase_id,'新建采购退货'.$id,false,'Purchase');
$Record->insert($id,(isset($_REQUEST['id'])?'修改':'保存').'采购退货',false,'Purchasereturn');
$model = M('PurchasereturnDetails');
$model->where("pid=$id")->delete();
foreach ($gp as &$v){
$v['pid'] = $id;
$model->add($v);
}
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
$model = D('Purchasereturn');
$vo = $model->getInfo($id);
$this->assign('vo',$vo['base']);
$details = $model->getDetails($id);
$this->assign('details',$details);
}
public function show()
{
$vo = $this->__get('vo');
$this->assign('title','采购退货_'.$vo['id'].'_'.$vo['abstract']);
$this->display();
}
public function edit()
{
$this->setTitle('修改采购退货单');
$id = intval($_REQUEST['id']);
$model = D('Purchasereturn');
$vo = $model->getInfo($id);
$this->assign('vo',$vo['base']);
$purchase_id = $vo['base']['purchase_id'];
$details = $model->getDetails($id);
$list = D('Purchase')->getDetails($purchase_id);
foreach ($list['details'] as &$v)
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
$this->assign('list',$list['details']);
$this->display();
}
public function afterApprove($success)
{
$id = intval($_REQUEST['id']);
$model = D('Purchasereturn');
$vo = $model->where("id=$id")->find();
$data['id'] = $id;
$data['status'] = 5;
$model->save($data);
$purchase_id = $vo['purchase_id'];
$model = D('Purchase');
$details = $model->getDetails($purchase_id);
$PD = M('PurchaseDetails');
$POD = M('PurchaseOrderDetails');
$vo = M('PurchasereturnDetails')->where("pid=$id")->select();
foreach ($vo as $v)
{
$product_id = $v['product_id'];
$qty = $v['qty'];
foreach ($details['details'] as $val)
{
if ($val['product_id'] == $product_id)
{
$PD->where(array('id'=>$val['id']))->setDec('qty',$qty);
break;
}
}
foreach ($details['orders'] as $val)
{
if ($val['product_id'] == $product_id &&$qty>0)
{
$data = array();
$data['id'] = $val['id'];
$data['qty'] = ($qty >$val['qty']) ?0: ($val['qty'] -$qty);
$qty = $data['qty'];
$POD->save($data);
}
}
}
$model->reCalculate($purchase_id);
D('Record')->insert($purchase_id,'采购退货单：'.$id,false,'purchase');
}
public function delete()
{
parent::delete();
}
}
?>