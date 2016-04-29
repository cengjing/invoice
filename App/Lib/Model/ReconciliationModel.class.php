<?php

class ReconciliationModel extends BaseModel 
{
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$ret['owner'][] = $vo['uid'];
$contact_id = $vo['contact_id'];
$vo['uid'] = getUserInfoById($vo['uid']);
$ret['base'] = $vo;
$vo = M('Contacts')->where("id=$contact_id")->find();
$ret['contact'] = $vo;
$company_id = $vo['company_id'];
$ret['base']['contact'] = $vo['name'];
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['base']['company'] = $vo['company'];
return $ret;
}
}?>