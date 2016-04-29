<?php

class ActivityModel extends BaseModel
{
protected $permModel = 'Accounts';
protected $_validate = array (
array ('abstract','require','请填写记录主题。'),
);
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
array ('status','1'),
);
public function getInfo ($id)
{
$vo = $this->where("id=$id")->find();
$company_id = $vo['company_id'];
$contact_id = $vo['contact_id'];
$ret['base'] = $vo;
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['owner'][] = $vo['assignto'];
$ret['base']['assignto'] = getUserInfoById($vo['assignto']);
$ret['base']['company'] = $vo['company'];
$vo = M('Contacts')->where("id=$contact_id")->find();
$ret['contact'] = $vo;
$ret['base']['contact'] = $vo['name'];
return $ret;
}
}?>