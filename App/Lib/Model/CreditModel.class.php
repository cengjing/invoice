<?php

class CreditModel extends BaseModel 
{
protected $_validate = array (
array ('company_id','require','请选择客户单位。'),
array ('company_id','','已经存在的客户单位信用政策。',0,'unique',Model::MODEL_BOTH ),
array ('amount','require','请填写信用额度。'),
);
public function getInfo ($id)
{
$vo = $this->where("id=$id")->find();
$company_id = $vo['company_id'];
$ret['owner'][] = $vo['uid'];
$ret['base'] = $vo;
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['base']['company'] = $vo['company'];
return $ret;
}
public function getAccountCredit($id)
{
$vo = $this->where(array('company_id'=>$id,'stauts'=>1))->find();
$maxCredit = (null == $vo)?floatval(getConfigValue('company_credit_value')):$vo['amount'];
if($maxCredit<=0)$maxCredit='无限制';
return $maxCredit;
}
}?>