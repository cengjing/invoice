<?php

class AllotModel extends BaseModel 
{
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$ret['base'] = $vo;
$ret['owner'][] = $vo['uid'];
return $ret;
}
public function getDetails($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'allot_details A';
$B = $prefix.'products B';
$details = M()->query("SELECT A.*,A.qty sum_qty,B.productname,B.catno,B.description,B.ch_description,B.unit,B.spec 
								FROM $A
								LEFT JOIN $B ON A.product_id=B.id
								WHERE allot_id=$id");
return $details;
}
public function finish($id)
{
$data['id'] = $id;
$data['status'] = 5;
$this->save($data);
}
}?>