<?php

class ReceiptModel extends BaseModel
{
protected $_validate = array (
array ('company_id','require','请选择客户！'),
array ('amount','require','请填写收款金额！'),
array ('category','require','请选择收款类型！'),
array ('type_id','require','请选择结算方式！'),
);
protected $_auto = array (
array ('status',1),
);
protected $_status = array (
'1'=>'已保存',
'2'=>'等待审核',
'3'=>'已审核',
'4'=>'退回审核',
'5'=>'完成',
'6'=>'删除',
);
protected $_type = array (
'1'=>'预收款',
'2'=>'暂存款',
'3'=>'发票收款',
'4'=>'退货收款',
'5'=>'其它收款'
);
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$ret['base'] = $vo;
$company_id = $vo['company_id'];
$contact_id = $vo['contact_id'];
$ret['owner'][] = $vo['uid'];
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['owner'][] = $vo['assignto'];
$ret['base']['company'] = $vo['company'];
$vo = M('Contacts')->where("id=$contact_id")->find();
$ret['contact'] = $vo;
$ret['base']['contact'] = $vo['name'];
return $ret;
}
public function getDetails($contact_id)
{
$map['contact_id'] = $contact_id;
$map['status'] = 3;
$vo = $this->where($map)->order('id ASC')->select();
foreach ($vo as &$val)
{
$val['loan_total'] = number_format($val['amount'] -$val['write_off'],2,".",",");
$val['amount'] -= $val['write_off'];
}
return $vo;
}
public function writeOff($id,$amount)
{
$map['id'] = $id;
$data['id'] = $id;
$vo = $this->where($map)->find();
if($vo['write_off'] +$amount <$vo['amount'])
{
$data['write_off'] = $vo['write_off'] +$amount;
}else if ($vo['write_off'] +$amount == $vo['amount']) {
$data['write_off'] = $vo['amount'];
$data['status'] = 5;
}
$ret = $this->save($data);
return $ret?true: false;
}
}?>