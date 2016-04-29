<?php

class DiscountModel extends BaseModel 
{
protected $_validate = array (
array ('discount','require','请填写折扣数值'),
);
public function getInfo ($id)
{
$vo = $this->where("id=$id")->find();
$ret['owner'][] = $vo['uid'];
$company_id = $vo['company_id'];
$ret['base'] = $vo;
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['base']['company'] = $vo['company'];
return $ret;
}
}?>