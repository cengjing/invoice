<?php

class PurchasereturnModel extends BaseModel
{
protected $tableName = 'purchasereturn';
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
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
$A = $prefix.'purchasereturn_details A';
$B = $prefix.'products B';
$details = M()->query("SELECT A.*,B.productname,B.catno,B.mfr_part_no,B.description,B.ch_description,B.unit,B.spec FROM $A 
							LEFT JOIN $B ON A.product_id=B.id
							WHERE A.pid=$id");
return $details;
}
}?>