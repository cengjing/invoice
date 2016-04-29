<?php

class PurchaseinvoiceModel extends BaseModel
{
protected $tableName = 'purchaseinvoice';
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
protected $_status = array (
'3'=>'已开票',
'5'=>'完成',
'6'=>'删除',
);
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$ret['owner'][] = $vo['uid'];
$ret['base'] = $vo;
return $ret;
}
public function getDetails($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'purchaseinvoice_details A';
$C = $prefix.'products C';
$details = M()->query("SELECT 
								A.id,A.pid,A.qty,A.purchase_id,A.price,A.unit details_unit,A.spec details_spec,
								C.id product_id,C.productname,C.catno,C.mfr_part_no,C.description,C.ch_description,C.unit,C.spec 
							FROM $A 
							LEFt JOIN $C ON A.product_id=C.id
							WHERE A.pid=$id");
return $details;
}
}?>