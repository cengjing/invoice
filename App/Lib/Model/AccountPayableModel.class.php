<?php

class AccountPayableModel extends BaseModel 
{
protected $tableName = 'account_payable';
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
protected $_type = array (
'1'=>'采购发票',
'2'=>'销售退货',
'3'=>'预付款',
'4'=>'暂存款',
'10'=>'其它应付款'
);
public function autoCreate($vender_id,$amount,$from_id,$type)
{
$data['vender_id']		= $vender_id;
$data['amount']			= $amount;
$data['from_id']		= $from_id;
switch ($type)
{
case 1:
$loan = 1;
break;
case 2:
$loan = 1;
break;
case 3:
$loan = -1;
break;
case 4:
$loan = -1;
break;
}
$data['type'] 			= $type;
$data['loan'] 			= $loan;
$data['cTime'] 			= time();
$data['status'] 		= 3;
$data['uid']			= session(C('USER_AUTH_KEY'));
$data['auto_create'] 	= 1;
$id = $this->add($data);
if($id)
{
$id = $this->saveId($id);
}
else
{
return false;
}
return $id;
}
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$vender_id = intval($vo['vender_id']);
$contact_id = intval($vo['contact_id']);
$ret['owner'][] = $vo['uid'];
$vo['loan'] = ($vo['loan']==-1)?'贷':'借';
if ($vo['type'] == 1 &&$vo['from_id'] != '')
{
$from_id = $vo['from_id'];
$vo['from_id'] = "<a href='__APP__/purchaseinvoice/show/id/$from_id'>$from_id</a>";
}
if ($vo['type'] == 2 &&$vo['from_id'] != '')
{
$from_id = $vo['from_id'];
$vo['from_id'] = "<a href='__APP__/returns/show/id/$from_id'>$from_id</a>";
}
if (($vo['type'] == 3 ||$vo['type'] == 4) &&$vo['from_id'] != '')
{
$from_id = $vo['from_id'];
$vo['from_id'] = "<a href='__APP__/receipt/show/id/$from_id'>$from_id</a>";
}
$vo['uid'] = getUserInfoById($vo['uid']);
$ret['base'] = $vo;
if($vender_id >0)
{
$vo = M('Vender')->where("id=$vender_id")->find();
$ret['vender'] = $vo;
$ret['base']['vender'] = $vo['company'];
$ret['base']['company'] = $vo['company'];
$ret['base']['contact'] = $vo['contact'];
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
$val['type_value'] = 1;
}
return $vo;
}
public function writeOff($id,$amount)
{
$map['id'] = $id;
$data['id'] = $id;
$vo = $this->where($map)->find();
$document_id = intval($vo['from_id']);
if($vo['write_off'] +$amount <$vo['amount'])
{
$data['write_off'] = $vo['write_off'] +$amount;
}else if ($vo['write_off'] +$amount == $vo['amount']) {
$data['write_off'] = $vo['amount'];
$data['status'] = 5;
$finished  = true;
}
$ret = $this->save($data);
if($ret == false) return false;
$record = D('Record');
switch ($vo['type'])
{
case 1:
if($finished)
{
unset($data);
$data['status'] 	= 5;
$data['id'] 		= $document_id;
$ret = M('Purchaseinvoice')->save($data);
if($ret == false) return false;
}
$details = M('PurchaseinvoiceDetails')->where("pid=$document_id")->group('purchase_id')->field('purchase_id,SUM(qty*price) amount')->select();
$model = D('Purchase');
foreach ($details as $val)
{
$purchase_id = $val['purchase_id'];
if($amount >0)
{
if($val['amount'] <= $amount)
{
$payment = $val['amount'];
$amount -= $val['amount'];
}
else 
{
$payment = $amount;
$amount = 0;
}
$purchase_vo = $model->where("id=$purchase_id")->find();
if($purchase_vo['amount'] >= $payment +$purchase_vo['payment'])
{
unset($data);
$data['id']			= $purchase_id;
$data['payment'] = $payment +$purchase_vo['payment'];
$ret = $model->save($data);
if($ret == false) return false;
if($purchase_vo['amount'] == $payment +$purchase_vo['payment'])
{
$model->autoCheck($purchase_id);
}
}
}
}
break;
case 2:
$model = D('Salesorder');
$vo = $model->where(array('id'=>$document_id))->find();
if($vo['collection'] <$amount)return false;
$model->execute("UPDATE __TABLE__ SET collection=collection-$amount WHERE id=$document_id");
$record->insert($document_id,"订单退款核销：$amount",false,'Salesorder');
$model->autoCheck($document_id);
break;
}
return true;
}
}?>