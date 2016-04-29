<?php

class StocktakingModel extends BaseModel 
{
protected $_validate = array (
array ('inventory_id','require','请选择盘点仓库！'),
array ('abstract','require','请填写摘要！'),
);
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
public function getDetails($id,$warehouse_id)
{
$dbfield = getWarehouseInfo($warehouse_id);
$dbfield = $dbfield['dbfield'];
$prefix = C('DB_PREFIX');
$A = $prefix.'stocktaking_details A';
$B = $prefix.'products B';
$C = $prefix.'warehouse C';
$Sql = "SELECT A.*,B.productname,B.catno,B.description,B.ch_description,B.unit,B.spec,C.$dbfield stock_qty FROM $A
			LEFT JOIN $B ON A.product_id=B.id
			LEFT JOIN $C ON A.product_id=C.product_id
			WHERE st_id=$id";
$details = M()->query($Sql);
foreach ($details as &$v){
$v['less'] = 0;
$v['more'] = 0;
if($v['stock_qty'] >$v['qty']){
$v['less'] = $v['stock_qty'] -$v['qty'];
}else if($v['stock_qty'] <$v['qty']){
$v['more'] = $v['qty'] -$v['stock_qty'];
}
}
return $details;
}
public function checkDone($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'stocktaking A';
$B = $prefix.'delivery B';
$C = $prefix.'entry C';
$vo = $this->query("SELECT A.delivery_id,A.entry_id,B.status delivery_status,C.status entry_status FROM $A
						LEFT JOIN $B ON A.delivery_id=B.id
						LEFT JOIN $C ON A.entry_id=C.id
						WHERE A.id=$id");
$delivery = false;
$entry = false;
$v = $vo[0];
if($v['delivery_id'] == '')
{
$delivery = true;
}else{
if($v['delivery_status'] == 2){
$delivery = true;
}
}
if($v['entry_id'] == '')
{
$entry = true;
}else{
if($v['entry_status'] == 2){
$entry = true;
}
}
if($delivery &&$entry)
{
$data['id'] = $id;
$data['status'] = 5;
$this->save($data);
}
}
}?>