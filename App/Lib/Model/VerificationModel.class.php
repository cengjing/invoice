<?php

class VerificationModel extends BaseModel 
{
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$ret['owner'][] = $vo['uid'];
$vender_id = intval($vo['vender_id']);
$contact_id = intval($vo['contact_id']);
$vo['uid'] = getUserInfoById($vo['uid']);
$ret['base'] = $vo;
if($vender_id >0)
{
$vo = M('Vender')->where("id=$vender_id")->find();
$ret['vender'] = $vo;
$ret['base']['vender'] = "<a href='__APP__/vender/show/id/".$vo['id']."'>".$vo['company'].' - '.$vo['contact']."</a>";
}
if($contact_id >0)
{
$vo = M('Contacts')->where(array('id'=>$contact_id))->field('id,name,company_id')->find();
$ret['contact'] = $vo;
$name = $vo['name'];
$company_id = $vo['company_id'];
$vo = M('Accounts')->where(array('id'=>$company_id))->field('id,company')->find();
$company = $vo['company'];
$ret['base']['company'] = "<a href='__APP__/accounts/show/id/".$company_id."'>".$company."</a>";
$ret['base']['contact'] = "<a href='__APP__/contacts/show/id/".$contact_id."'>".$name."</a>";
$ret['base']['contactName'] = $ret['base']['company'].' - '.$ret['base']['contact'];
}
return $ret;
}
}?>