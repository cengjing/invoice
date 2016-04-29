<?php

class AccountReceivableModel extends BaseModel 
{
protected $tableName = 'account_receivable';
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
protected $_type = array (
'1'=>'销售发票',
'2'=>'销售退货',
'3'=>'预收款',
'4'=>'暂存款',
'5'=>'其它应收款'
);
public function autoCreate($vo,$type)
{
$data['company_id'] 	= $vo['company_id'];
$data['contact_id']		= $vo['contact_id'];
$data['amount']			= $vo['amount'];
$data['from_id']		= $vo['id'];
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
case 5:
$loan = 1;
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
return $id;
}
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$ret['owner'][] = $vo['uid'];
$contact_id = $vo['contact_id'];
$vo['loan'] = ($vo['loan']==-1)?'贷':'借';
if ($vo['type'] == 1 &&$vo['from_id'] != '')
{
$from_id = $vo['from_id'];
$vo['from_id'] = "<a href='__APP__/invoice/show/id/$from_id'>$from_id</a>";
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
$vo = M('Contacts')->where("id=$contact_id")->find();
$ret['contact'] = $vo;
$company_id = $vo['company_id'];
$ret['base']['contact'] = $vo['name'];
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['base']['company'] = $vo['company'];
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
$val['type_value'] = 1;
}
return $vo;
}
public function writeOff($id,$amount,$rid=null)
{
$map['id'] = $id;
$data['id'] = $id;
$vo = $this->where($map)->find();
if ($vo == null) return false;
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
switch ($vo['type'])
{
case 1:
if($finished)
{
unset($data);
$data['status'] 	= 4;
$data['id'] 		= $vo['from_id'];
$ret = M('Invoice')->save($data);
if($ret === false) return false;
}
$details = M('InvoiceOrder')->where('invoice_id='.$vo['from_id'])->select();
$model = D('Salesorder');
$record = D('Record');
$SC = M('SalesorderRecon');
foreach ($details as $val)
{
$order_id = $val['order_id'];
if($amount >0)
{
if($val['amount'] <= $amount)
{
$collection = $val['amount'];
$amount -= $val['amount'];
}
else 
{
$collection = $amount;
$amount = 0;
}
if(!empty($order_id))
{
$order_vo = $model->where("id=$order_id")->find();
if($order_vo == null) return false;
if($order_vo['total'] >= $collection +$order_vo['collection'])
{
unset($data);
$data['id']			= $order_id;
$data['collection'] = $collection +$order_vo['collection'];
$ret = $model->save($data);
if($ret === false) return false;
if(!empty($rid))
{
unset($data);
$data['order_id']		= $order_id;
$data['amount']			= $collection;
$data['rid']			= $rid;
$ret = $SC->add($data);
if($ret === false) return false;
}
$record->insert($order_id,"收款核销：$collection",false,'Salesorder');
if($order_vo['total'] == $collection +$order_vo['collection'])
{
$model->autoCheck($order_id);
}
}
}
}
}
break;
}
return true;
}
}
?>