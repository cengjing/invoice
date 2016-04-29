<?php

class PurchaseinvoiceAction extends WorkflowAction 
{
public function index()
{
$ret = $this->getFilter('Purchaseinvoice','A.');
$A = $this->prefix.'purchaseinvoice A ';
$from 		= " FROM $A ";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.* $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->setTitle('采购发票列表');
$this->display();
}
public function getPurchaseDetails()
{
if(!$this->isAjax())return;
$ret = $this->_purchaseDetails();
$this->assign('list',$ret);
$this->display('purchase_details');
}
private function _purchaseDetails()
{
$id = intval($_REQUEST['vender_id']);
$pid = intval($_REQUEST['pur_id']);
$A = $this->prefix.'purchase_details A';
$B = $this->prefix.'purchase B';
$C = $this->prefix.'products C';
if($pid>0){
$condition = " B.id=$pid ";
}elseif($id>0){
$condition = " B.vender_id=$id ";
}else{
return;
}
$Sql = "SELECT A.*,C.productname,C.catno,C.description,C.ch_description,B.cTime,B.abstract,B.amount,B.invoice,B.other_fund,B.freight,B.sum,B.payment,C.unit,C.spec 
				FROM $A 
				LEFT JOIN $B ON (A.purchase_id=B.id) 
				LEFT JOIN $C ON (C.id=A.product_id)
				WHERE $condition AND B.invoice<B.amount AND B.status=3 ORDER BY C.catno ASC";
$vo = M()->query($Sql);
$ret = array();
foreach ($vo as $v)
{
$product_id = $v['product_id'];
$purchase_id= $v['purchase_id'];
if (!isset($ret[$purchase_id]))
{
$ret[$purchase_id]['purchase_id']	= $v['purchase_id'];
$ret[$purchase_id]['cTime'] 		= getTime($v['cTime']);
$ret[$purchase_id]['total'] 		= $v['amount'];
$ret[$purchase_id]['invoice']		= $v['invoice'];
$ret[$purchase_id]['other_fund']	= $v['other_fund'];
$ret[$purchase_id]['freight']		= $v['freight'];
$ret[$purchase_id]['sum']			= $v['sum'];
$ret[$purchase_id]['payment'] 		= $v['payment'];
}
$ret[$purchase_id]['details'][] = $v;
}
return $ret;
}
public function add()
{
$this->setTitle('新建采购发票');
$A = $this->prefix.'purchase A';
$C = $this->prefix.'vender C';
$Sql = "SELECT SUM(A.amount-A.invoice) sum,C.company,C.id
				 FROM $A LEFT JOIN $C ON(A.vender_id=C.id) 
				 WHERE A.status=3 AND A.invoice<A.amount GROUP BY A.vender_id";
$vo = M()->query($Sql);
$this->assign('purchase_id',$_REQUEST['id']);
$this->assign('list',$vo);
$this->display();
}
public function insert()
{
$this->validate();
$purchase_id = intval($_REQUEST['pur_id']);
if($purchase_id >0){
$vo = M('Purchase')->where(array('id'=>$purchase_id))->find();
$_REQUEST['vender_id'] = $vo['vender_id'];
$_POST['vender_id'] = $vo['vender_id'];
}
$ret = $this->_purchaseDetails();
$gp = array();
$static = array();
$sum = 0;
foreach ( $_REQUEST['purchase_id'] as $k=>$v )
{
$qty = floatval($_REQUEST['qty'][$k]);
if( $qty >0 )
{
$purchase_id = $v;
$data = array();
$data['product_id']		= $_REQUEST['product_id'][$k];
$data['qty']			= $qty;
$data['purchase_id']	= $purchase_id;
$data['price']			= floatval($_REQUEST['price'][$k]);
$data['unit']			= $_REQUEST['unit'][$k];
$data['spec']			= $_REQUEST['spec'][$k];
$gp[] = $data;
$sum += $data['price']*$qty;
if(!isset($static[$purchase_id]))
{
$static[$purchase_id]['amount'] = $data['price']*$qty;
}
else
{
$static[$purchase_id]['amount'] += $data['price']*$qty;
}
}
}
if(count($gp) == 0 ||count($static) == 0)
{
$this->ajaxReturn('','请填写明细。',0);
}
foreach ($_REQUEST['pid'] as $k=>$v)
{
$of = str_replace(",",'',$_REQUEST['of'][$k]);
$ft = str_replace(",",'',$_REQUEST['ft'][$k]);
if(!isset($static[$v]))
{
$static[$v]['amount'] = floatval($of) +floatval($ft);
}
else
{
$static[$v]['amount'] += floatval($of) +floatval($ft);
}
$static[$v]['other_fund'] = $of;
$static[$v]['freight'] = $ft;
}
foreach ($static as $k=>$v)
{
if ( $ret[$k]['total']-$ret[$k]['invoice']<$v['amount'])
{
$this->ajaxReturn('',"采购订单：$k 的开票金额超出上限。".$ret['sql'],0);
}
}
$_POST['sum'] 		= $sum;
$amount = $sum +floatval($_POST['other_fund']) +floatval($_POST['freight']);
$_POST['amount'] 	= $amount;
$_POST['uid']		= $this->uid;
$_POST['status'] 	= 3;
$model = D('Purchaseinvoice');
$id = $model->saveInfo();
if ($id !== false)
{
$PD = M('PurchaseinvoiceDetails');
foreach ($gp as &$v)
{
$v['pid'] = $id;
$PD->add($v);
}
$model = D('Purchase');
$Record = D('Record');
$PG = M('PurchaseinvoiceGroup');
foreach ($static as $k=>$v)
{
$model->where(array('id'=>$k))->setInc('invoice',$v['amount']);
$Record->insert($k,'新建采购发票'.$id,false,'Purchase');
$model->autoCheck($k);
$data = array();
$data['pid']			= $id;
$data['purchase_id']	= $k;
$data['amount']			= $v['amount'];
$data['other_fund']		= $v['other_fund'];
$data['freight']		= $v['freight'];
$PG->add($data);
}
$Record->insert($id,'保存采购发票',false,$this->getActionName());
$vender_id = intval($_REQUEST['vender_id']);
D('AccountPayable')->autoCreate($vender_id,$amount,$id,1);
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
$id = intval($_REQUEST['id']);
$model = D('Purchaseinvoice');
$vo = $model->getInfo($id);
$this->assign('vo',$vo['base']);
$details = $model->getDetails($id);
$this->assign('details',$details);
}
public function show()
{
$vo = $this->__get('vo');
$this->assign('title','采购发票-'.$vo['id'].'-'.$vo['abstract']);
$this->display();
}
public function delete()
{
$id = intval($_REQUEST['id']);
$AP = M('AccountPayable');
$map['type'] 	= 1;
$map['from_id'] = $id;
$map['status'] 	= 3;
$vo  = $AP->where($map)->find();
if($vo == null)
{
$this->error('应付款已经被核销，不能再删除采购发票。');
}
$PI = D('Purchaseinvoice');
$result = $PI->getInfo($id);
if($result['base']['status'] != 3)
{
$this->error('错误的采购发票状态，不能执行删除');
}
$data['status'] = 6;
$data['id']		= $vo['id'];
$AP->save($data);
unset($data);
$data['status'] = 6;
$data['id']		= $result['base']['id'];
$PI->save($data);
$vo = M('PurchaseinvoiceGroup')->where(array('pid'=>$id))->select();
$model = D('Purchase');
$Record = D('Record');
foreach ($vo as $v)
{
$purchase_id = $v['purchase_id'];
$model->where(array('id'=>$purchase_id))->setDec('invoice',$v['amount']);
$Record->insert($purchase_id,'删除采购发票'.$id,false,'Purchase');
$model->autoCheck($purchase_id);
}
$Record->insert($id,'删除采购发票',false,'Purchaseinvoice');
$this->success('成功删除采购发票');
}
}?>