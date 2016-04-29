<?php

class DeliveryModel extends BaseModel 
{
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
array ('status','1'),
);
protected $_type = array (
'1'=>'销售发货',
'2'=>'调拨发货',
'3'=>'盘亏出库'
);
protected $_status = array (
'1'=>'准备发货',
'2'=>'已发货',
'3'=>'删除',
'4'=>'申请退回',
);
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
if($vo === null)
{
return null;
}
$contact_id = $vo['contact_id'];
$ret['owner'][] = $vo['uid'];
$ret['base'] = $vo;
$vo['act_uid'] = getUserInfoById($vo['act_uid']);
$vo['uid'] = getUserInfoById($vo['uid']);
$vo = M('Contacts')->where("id=$contact_id")->find();
$company_id = $vo['company_id'];
$ret['contact'] = $vo;
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['company'] = $vo;
$ret['owner'][] = $vo['assignto'];
$ret['owner'] = array_unique($ret['owner']);
return $ret;
}
public function getDetails($id)
{
$prefix = C( 'DB_PREFIX');
$B = $prefix.'products B';
$vo = M('DeliveryDetails')->query("SELECT A.*,B.catno,B.productname,B.description,B.ch_description 
								FROM __TABLE__ A LEFT JOIN $B ON A.product_id=B.id WHERE A.delivery_id=$id ORDER BY order_id,product_id");
return $vo;
}
}?>