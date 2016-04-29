<?php

class PaymentModel extends BaseModel  
{
protected $_validate = array (
array ('type_id','require','请选择付款类型！'),
array ('amount','require','请填写收款金额！'),
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
'1'=>'预付款',
'2'=>'暂存款',
'3'=>'采购付款',
'4'=>'退货收款',
'5'=>'订单退款',
'10'=>'其它付款'
);
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$ret['base'] = $vo;
$vender_id = intval($vo['vender_id']);
$contact_id = intval($vo['contact_id']);
$ret['owner'][] = $vo['uid'];
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
public function getDetails($vender_id,$contact_id,$assignto)
{
if($vender_id>0){
$map['vender_id'] = $vender_id;
}elseif($contact_id>0){
$map['contact_id'] = $contact_id;
}else{
$map['assignto'] = $assignto;
}
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