<?php

class EntryModel extends BaseModel 
{
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
array ('status','1'),
);
protected $_type = array (
'1'=>'采购入库',
'2'=>'调拨入库',
'3'=>'退货入库',
'4'=>'盘盈入库'
);
protected $_status = array (
'1'=>'准备入库',
'2'=>'完成入库',
'3'=>'删除',
'4'=>'退回',
);
public function transfer ($fid,$warehouse_id,$type,$details)
{
$data['type'] 			= $type;
$data['from_id'] 		= $fid;
$vo = $this->where($data)->find();
$data['warehouse_id']	= $warehouse_id;
$exist = false;
if($vo)
{
$exist = true;
$id = $vo['id'];
$data['id'] 		= $id;
$data['status'] 	= 1;
$this->save($data);
}
else 
{
$data['status']	= 1;
$data['cTime'] 	= time();
$data['uid']	= session(C('USER_AUTH_KEY'));
$id = $this->add($data);
$id = $this->saveId($id);
}
$model = M('EntryDetails');
if($exist &&count($details) >0)
{
$model->where("entry_id=$id")->delete();
}
foreach ($details as $v)
{
$data				= 	array();
$data['entry_id']	=	$id;
$data['product_id']	=	$v['product_id'];
$data['qty']		=	$v['qty'];
$model->add($data);
}
return $id;
}
public function finish($id)
{
$vo = $this->where("id=$id")->find();
$warehouse_id = $vo['warehouse_id'];
$from_id = $vo['from_id'];
$type = $vo['type'];
$info = getWarehouseInfo($warehouse_id);
$dbfield = $info['dbfield'];
$ED = M('EntryDetails');
$vo = $ED->where("entry_id=$id")->select();
$W = D('Warehouse');
if($type == 1){
$arrivalModel = M('ArrivalDetails');
$POD = M('PurchaseOrderDetails');
}
foreach ($vo as $v) 
{
$product_id = $v['product_id'];
$qty = $v['qty'];
$price = -1;
if($type == 1){
$ret = $arrivalModel->where(array('arrival_id'=>$from_id,'product_id'=>$product_id))
->field('purchase_id')->find();
if(!empty($ret)){
$purchase_id = $ret['purchase_id'];
$ret = $POD->where(array('purchase_id'=>$purchase_id,'product_id'=>$product_id))
->field('SUM(purchase_price*qty)/SUM(qty) price')->find();
$price = $ret['price'];
}
}
$stock_qty = $W->Inc($dbfield,$product_id,$qty,$price);
$data = array();
$data['product_id'] 	= $product_id;
$data['stock_qty'] 		= $stock_qty;
$data['id'] 			= $v['id'];
$ED->save($data);
}
switch ($type){
case 1:
D('Arrival')->finish($from_id);
break;
case 2:
D('Allot')->finish($from_id);
break;
case 3:
D('Returns')->finish($from_id);
break;
case 4:
D('Stocktaking')->checkDone($from_id);
break;
}
}
public function back ($id)
{
$vo = $this->where("id=$id")->find();
$warehouse_id = $vo['warehouse_id'];
$from_id = $vo['from_id'];
$type = $vo['type'];
switch ($type){
case 1:
D('Arrival')->back($from_id);
break;
}
}
}?>