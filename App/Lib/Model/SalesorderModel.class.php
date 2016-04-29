<?php

class SalesorderModel extends BaseModel 
{
protected $tableName = 'salesorder';
protected $_validate = array (
array ('company_id','require','请选择客户！'),
array ('contact_id','require','请选择联系人！'),
array ('assignto','require','请选择负责人！'),
array ('abstract','require','请填写摘要！'),
);
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
protected $_status = array (
'1'=>'已保存',
'2'=>'等待审核',
'3'=>'已审核',
'4'=>'退回审核',
'5'=>'完成',
'6'=>'删除',
);
public function getInfo ($id)
{
if ($id<=0)return false;
$vo = $this->where(array('id'=>$id))->find();
if($vo === null)
{
return null;
}
$company_id = $vo['company_id'];
$contact_id = $vo['contact_id'];
$vo['total'] = formatCurrency($vo['total']);
$vo['sum'] = formatCurrency($vo['sum']);
$vo['other_fund'] = formatCurrency($vo['other_fund']);
$vo['freight'] = formatCurrency($vo['freight']);
$vo['invoice'] = formatCurrency($vo['invoice']);
$vo['collection'] = formatCurrency($vo['collection']);
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
$ret['base']['address'] = $vo['address'];
$ret['base']['fax'] = $vo['regioncode'].'-'.$vo['fax'];
$ret['base']['mobilephone'] = $vo['mobilephone'];
$ret['base']['phone'] = $vo['regioncode'].'-'.$vo['phone'];
$ret['owner'] = array_unique($ret['owner']);
return $ret;
}
public function getDetails($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'salesorder_details A';
$B = $prefix.'products B';
$details = M()->query("SELECT A.*,B.productname,B.catno,B.description,B.ch_description,B.unit,B.spec,B.unit_price FROM $A
			LEFT JOIN $B ON A.product_id=B.id
			WHERE order_id=$id ORDER BY A.id ASC");
foreach ($details as &$v)
{
$v['sum_price'] = formatCurrency($v['sum_price']);
}
return $details;
}
public function autoCheck($id)
{
$invoice = false;
$pay = false;
$ship = true;
if(empty($id))return;
$vo = $this->where("id=$id")->find();
$company_id = $vo['company_id '];
$cd['id'] = $vo['contact_id'];
if( floatval($vo['total']) == floatval($vo['invoice']) ){
$invoice = true;
if( floatval($vo['invoice_completion_date']) == 0 ){
$data['invoice_completion_date'] = time();
}
}else{
$data['invoice_completion_date'] = '';
}
if( floatval($vo['total']) == floatval($vo['collection']) ){
$pay = true;
if( floatval($vo['payment_completion_date']) == 0 ){
$data['payment_completion_date'] = time();
}
}else {
$data['payment_completion_date'] = '';
}
$details = M('SalesorderDetails')->field('qty,deli_qty')->where("order_id=$id AND qty>0")->select();
if($details == null){
$data['status']			 = 6;
}else{
foreach ($details as $v){
if( $v['qty'] != $v['deli_qty']){
$ship = false;break;
}
}
if($ship){
if( floatval($vo['ship_completion_date']) == 0 ){
$data['ship_completion_date'] = time();
}
}else {
$data['ship_completion_date'] = '';
}
if($invoice &&$pay &&$ship){
$data['status']			 = 5;
if( floatval($vo ['completion_date']) == 0){
$data['completion_date'] = time();
}
$cd['order_time'] = time();
M('Contacts')->save($cd);
D('AccountsStat')->stat($company_id,'salesorder');
D('Record')->insert($id,'订单完成',false,'salesorder');
}else{
$data['status']			 = 3;
$data['completion_date'] = '';
}
}
$data['id'] = $id;
$this->save($data);
}
public function getDelivery($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'delivery A';
$B = $prefix.'delivery_details B';
$sql = "SELECT A.* FROM $A LEFT JOIN $B ON (A.id=B.delivery_id) WHERE B.order_id=$id AND A.type=1 GROUP BY A.id ORDER BY A.id DESC";
$vo = $this->query($sql);
return $vo;
}
public function reCalculate($id)
{
$SD = M('SalesorderDetails');
$SD->execute("UPDATE __TABLE__ SET sum_price=(sale_price+other_price)*qty WHERE order_id=$id");
$vo = $SD->where("order_id=$id")->select();
$other = 0;
$sum = 0;
foreach ($vo as $v)
{
$other += $v['other_price'] * $v['qty'];
$sum += $v['sale_price'] * $v['qty'];
}
$this->execute("UPDATE __TABLE__ SET other_fund=$other,sum=$sum,total=sum+other_fund+freight WHERE id=$id");
$this->autoCheck($id);
}
}?>