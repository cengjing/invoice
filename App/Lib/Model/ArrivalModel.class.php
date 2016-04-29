<?php

class ArrivalModel extends BaseModel
{
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
public function getDetails($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'arrival_details A';
$B = $prefix.'products B';
$details = M()->query("SELECT A.*,B.productname,B.catno,B.description,B.ch_description,B.unit,B.spec,B.mfr_part_no FROM $A
			LEFT JOIN $B ON A.product_id=B.id
			WHERE arrival_id=$id");
return $details;
}
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$ret['base'] = $vo;
$ret['owner'][] = $vo['uid'];
return $ret;
}
public function finish($id)
{
$data['id'] = $id;
$data['status'] = 5;
$this->save($data);
$vo = $this->getDetails($id);
$PD = M('PurchaseDetails');
$POD = M('PurchaseOrderDetails');
$notify = array();
foreach ($vo as $v)
{
$purchase_id = $v['purchase_id'];
$product_id = $v['product_id'];
$qty = $v['qty'];
$PD->where(array('purchase_id'=>$purchase_id,'product_id'=>$product_id))->setInc('entry_qty',$qty);
$pid[] = $v['purchase_id'];
$orders = $POD->where(array('purchase_id'=>$purchase_id,'product_id'=>$product_id))->field('order_id')->select();
foreach ($orders as $val){
$order_id = $val['order_id'];
if($val['order_id'] != ''){
$notify[$order_id]['order_id'] = $order_id;
$notify[$order_id]['content'] .= '<p>'.$v['productname'].'，'.$v['catno'].'</p>';
}
}
}
$model = D('Record');
$model->insert($id,'到货单完成入库',false,'Arrival');
foreach ($notify as $v)
{
$order_id = $v['order_id'];
$content = $v['content'];
$model->insert($order_id,"<p>采购到货：</p>".$content,true,'Salesorder');
}
$pid = array_unique($pid);
$model = D('Purchase');
foreach ($pid as $v)
{
$model->autoCheck($v);
}
}
public function back($id)
{
$map['id'] = $id;
$data['status'] = 1;
$this->where($map)->save($data);
D('Record')->insert($id,'退回到货单',true,'Arrival');
}
}?>