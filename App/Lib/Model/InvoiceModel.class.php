<?php

class InvoiceModel extends BaseModel 
{
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
protected $_status = array (
'1'=>'提交开票',
'2'=>'已开票',
'3'=>'已删除',
'4'=>'已收款',
);
public function getInfo ($id)
{
$vo = $this->where("id=$id")->find();
if($vo === null)
{
return null;
}
$contact_id = $vo['contact_id'];
$ret['owner'][] = $vo['apply_uid'];
$ret['base'] = $vo;
$vo['apply_uid'] = getUserInfoById($vo['apply_uid']);
$vo['act_uid'] = getUserInfoById($vo['act_uid']);
$vo = M('Contacts')->where("id=$contact_id")->find();
$ret['contact'] = $vo;
$company_id = $vo['company_id'];
$ret['base']['contact'] = $vo['name'];
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['owner'][] = $vo['assignto'];
$ret['base']['company'] = $vo['company'];
return $ret;
}
public function getDetails($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'invoice_details A';
$B = $prefix.'products B';
$details = M()->query("SELECT A.*,B.productname,B.catno,B.description,B.ch_description,B.unit_price FROM $A
			LEFT JOIN $B ON A.product_id=B.id
			WHERE invoice_id=$id ORDER BY A.id ASC");
return $details;
}
}?>