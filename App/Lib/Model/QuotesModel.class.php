<?php

class QuotesModel extends BaseModel 
{
protected $_validate = array (
array ('company_id','require','请选择客户！'),
array ('contact_id','require','请选择联系人！'),
array ('assignto','require','请选择负责人！'),
);
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
protected $_status = array (
'1'=>'已保存',
'5'=>'完成',
'6'=>'删除',
);
public function getInfo ($id)
{
$vo = $this->where("id=$id")->find();
if($vo === null)
{
return null;
}
$company_id = $vo['company_id'];
$contact_id = $vo['contact_id'];
$ret['owner'][] = $vo['assignto'];
$ret['owner'][] = $vo['uid'];
$ret['base'] = $vo;
$vo['assignto'] = getUserInfoById($vo['assignto']);
$vo['uid'] = getUserInfoById($vo['uid']);
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['owner'][] = $vo['assignto'];
$ret['base']['company'] = $vo['company'];
$vo = M('Contacts')->where("id=$contact_id")->find();
$ret['contact'] = $vo;
$ret['base']['contact'] = $vo['name'];
$ret['base']['fax'] = $vo['regioncode'].'-'.$vo['fax'];
$ret['base']['mobilephone'] = $vo['mobilephone'];
$ret['base']['phone'] = $vo['regioncode'].'-'.$vo['phone'];
return $ret;
}
public function getDetails($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'quotes_details A';
$B = $prefix.'products B';
$details = M()->query("SELECT A.*,B.productname,B.catno,B.description,B.ch_description,B.unit,B.spec,B.unit_price FROM $A
			LEFT JOIN $B ON A.product_id=B.id
			WHERE quotes_id=$id ORDER BY A.id ASC");
return $details;
}
}?>