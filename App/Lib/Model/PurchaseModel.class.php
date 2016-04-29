<?php

class PurchaseModel extends BaseModel 
{
protected $_validate = array (
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
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$vender_id = $vo['vender_id'];
$ret['owner'][] = $vo['uid'];
$ret['base'] = $vo;
$vo = M('Vender')->where("id=$vender_id")->find();
$ret['vender'] = $vo;
return $ret;
}
public function getDetails($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'purchase_details A';
$B = $prefix.'products B';
$ret['group'] = M()->query("SELECT A.*,B.productname,B.catno,B.description,B.ch_description,B.unit,B.spec,B.mfr_part_no FROM $A
			LEFT JOIN $B ON A.product_id=B.id
			WHERE purchase_id=$id ORDER BY A.id ASC");
$A = $prefix.'purchase_order_details A';
$B = $prefix.'products B';
$vo = M()->query("SELECT A.*,B.productname,B.catno,B.description,B.ch_description,B.unit,B.spec,B.mfr_part_no FROM $A
			LEFT JOIN $B ON A.product_id=B.id
			WHERE purchase_id=$id ORDER BY A.id ASC");
foreach ($vo as $val)
{
if(intval($val['order_id'])>0)
{
$ret['orders'][] = $val;
}else{
$ret['def'][] = $val;
}
$ret['details'][] = $val;
}
return $ret;
}
public function calculateMrp()
{
$temp = S('purchase_qty');
if($temp != null) return $temp;
$prefix = C('DB_PREFIX');
$db = M();
$A = $prefix.'salesorder_details A';
$B = $prefix.'salesorder B';
$C = $prefix.'products C';
$sql = "SELECT A.product_id,SUM(A.qty-A.deli_qty) qty,C.vender_id,C.productname,C.purchase_price,C.catno,C.description,
							C.ch_description,C.mfr_part_no
							FROM $A 
							LEFT JOIN $B ON A.order_id=B.id 
							LEFT JOIN $C ON A.product_id=C.id
							WHERE B.status=3 AND A.qty-A.deli_qty>0 GROUP BY A.product_id";
$ret_order = $db->query($sql);
$ret_order = array2key($ret_order,'product_id');
$ret_order_details = $db->query("SELECT A.product_id,A.qty-A.deli_qty qty,A.order_id,A.sale_price FROM $A 
							LEFT JOIN $B ON A.order_id=B.id 
							WHERE B.status=3 AND A.qty-A.deli_qty>0 ORDER BY B.check_time ASC");
$vo = M('WarehouseName')->where('mrp=1')->field('dbfield')->select();
$dbfield = '';
foreach ($vo as $v)
$dbfield .= ($dbfield==''?'':'+').$v['dbfield'];
$ret_ivt = M('Warehouse')->where("$dbfield>0")->field("$dbfield qty,product_id")->select();
$ret_ivt = array2key($ret_ivt,'product_id');
$A = $prefix.'purchase_details A';
$B = $prefix.'purchase B';
$ret_pur = $db->query("SELECT A.product_id,SUM(A.qty-A.entry_qty) qty FROM $A
						LEFT JOIN $B ON A.purchase_id=B.id WHERE A.qty-A.entry_qty>0 AND B.status IN (1,2,3,4) AND B.mrp=1 GROUP BY A.product_id");
$ret_pur = array2key($ret_pur,'product_id');
$A = $prefix.'purchase_order_details A';
$ret_pur_details = $db->query("SELECT A.product_id,A.order_id,A.qty FROM $A
						LEFT JOIN $B ON A.purchase_id=B.id WHERE B.status IN (1,2,3,4) AND B.mrp=1");
$ret = array();
$venders = array();
foreach ($ret_order as $v){
$tmp1 = array();
$tmp2 = array();
$pid = $v['product_id'];
$qty = $v['qty'] -$ret_ivt[$pid]['qty'] -$ret_pur[$pid]['qty'];
$v['stock_qty'] = $ret_ivt[$pid]['qty'];
$v['pur_qty'] = $qty>0?$qty:0;
$venders[] = $v['vender_id'];
if($v['pur_qty']>0){
$own_qty = $ret_ivt[$pid]['qty'] +$ret_pur[$pid]['qty'];
foreach ($ret_order_details as $t){
if($t['product_id'] == $pid)$tmp1[$t['order_id']] = $t;
}
foreach ($ret_pur_details as $t){
if($t['product_id'] == $pid)$tmp2[$t['order_id']] = $t;
}
foreach ($tmp2 as $k=>$t){
if(isset($tmp1[$k])){
if($tmp1[$k]['qty'] <= $own_qty){
$own_qty -= $tmp1[$k]['qty'];
unset($tmp1[$k]);
}else {
$tmp1[$k]['pur_qty'] = $tmp1[$k]['qty']-$own_qty;
$own_qty = 0;
}
}
}
foreach ($tmp1 as $k=>$t){
if($t['qty'] <= $own_qty){
$own_qty -= $t['qty'];
unset($tmp1[$k]);
}else {
$tmp1[$k]['pur_qty'] = $tmp1[$k]['qty']-$own_qty;
$own_qty = 0;
}
}
}
$v['details'] = $tmp1;
$v['sum'] = $v['purchase_price'] * $v['pur_qty'];
$ret[$pid] = $v;
}
S('purchase_qty',$ret,600);
return $ret;
}
public function autoCheck($id)
{
$invoice = false;
$pay = false;
$ship = false;
$vo = $this->where("id=$id")->find();
if( floatval($vo['amount']) == floatval($vo['invoice']) ){
$invoice = true;
if( floatval($vo['invoice_completion_date']) == 0 ){
$data['invoice_completion_date'] = time();
}
}else{
$data['invoice_completion_date'] = '';
}
if( floatval($vo['amount']) == floatval($vo['payment']) ){
$pay = true;
if( floatval($vo['payment_completion_date']) == 0 ){
$data['payment_completion_date'] = time();
}
}else {
$data['payment_completion_date'] = '';
}
$details = M('PurchaseDetails')->where("purchase_id=$id AND qty>0 AND qty<>entry_qty")->select();
if($details == null){
$ship = true;
if( floatval($vo['ship_completion_date']) == 0 ){
$data['ship_completion_date'] = time();
}
}else {
$data['ship_completion_date'] = '';
}
if($invoice &&$pay &&$ship){
$data['status']			 = 5;
if(floatval($vo ['completion_date']) == 0){
$data['completion_date'] = time();
}
}else{
$data['status']			 = 3;
$data['completion_date'] = '';
}
$data['id'] = $id;
$this->save($data);
}
public function reCalculate($id)
{
$SD = M('PurchaseDetails');
$vo = $SD->where("purchase_id=$id")->select();
$sum = 0;
foreach ($vo as $v)
{
$sum += $v['price'] * $v['qty'];
}
$this->execute("UPDATE __TABLE__ SET amount=$sum WHERE id=$id");
$this->autoCheck($id);
}
}?>