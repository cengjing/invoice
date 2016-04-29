<?php

class WarehouseModel extends Model 
{
public function getPerm($owner=null)
{
$prefix = C('DB_PREFIX');
if($owner == null)$owner = session(C('USER_AUTH_KEY'));
$B = $prefix.'inventory_name B';
if(!session('?inventory_perm'))
{
$vo = M('UserIvt')->query("SELECT B.* FROM __TABLE__ A 
							LEFT JOIN $B ON A.inventory_id=B.id WHERE A.uid=$owner");
session('inventory_perm',$vo);
}
if(!session('?sales_inventory_perm'))
{
$vo = M('UserSalesIvt')->query("SELECT B.* FROM __TABLE__ A 
							LEFT JOIN $B ON A.inventory_id=B.id WHERE A.uid=$owner");
session('sales_inventory_perm',$vo);
}
}
public function checkPerm($inventory_id,$owner=null)
{
$this->getPerm($owner);
$perm = false;
$all = false;
foreach (session('inventory_perm') as $v){
if($v['id'] == $inventory_id){
$perm = true;
$all = true;
break;
}
}
foreach (session('sales_inventory_perm') as $v){
if($v['id'] == $inventory_id){
$perm = true;
break;
}
}
if(!$perm) return false;
$ret['perm'] = true;
$ret['all'] = $all;
return $ret;
}
public function Dec($warehouseName,$product_id,$qty)
{
$ret = $this->where(array('product_id'=>$product_id))->setDec($warehouseName,$qty);
if ($ret === false)return false;
$vo = $this->where("product_id=$product_id")->field($warehouseName)->find();
if($vo)
{
return $vo[$warehouseName];
}
else
{
return 0;
}
}
public function Inc($warehouseName,$product_id,$qty,$price = -1)
{
$p = $this->where(array('product_id'=>$product_id))->find();
if(!$p)
{
$data = array();
$data['product_id'] = $product_id;
$ret = $this->add($data);
if ($ret === false)return false;
}
if($price>=0)
{
$stock_qty = 0;
$vo = M('WarehouseName')->where('on_sale=1')->field('dbfield')->select();
foreach ($vo as $val)
$stock_qty += floatval($p[$val['dbfield']]);
$model = M('Products');
$vo = $model->where(array('id'=>$product_id))->field('stock_price,purchase_price')->find();
$stock_price = floatval($vo['stock_price']);
$purchase_price = floatval($vo['purchase_price']);
$stock_price = ($stock_price == 0)?$price:($stock_qty*$stock_price+$qty*$price)/($stock_qty+$qty);
if($stock_price == 0)$stock_price = $purchase_price;
$model->where(array('id'=>$product_id))->save(array('stock_price'=>$stock_price));
}
$ret = $this->where(array('product_id'=>$product_id))->setInc($warehouseName,$qty);
if ($ret === false)return false;
$vo = $this->where("product_id=$product_id")->field($warehouseName)->find();
if($vo)
{
return $vo[$warehouseName];
}
else
{
return 0;
}
}
public function getStockQty( $product_id )
{
$vo = M('WarehouseName')->where('on_sale=1')->select();
$field = '';
$data = array();
foreach ($vo as $v) 
{
$data[$v['dbfield']] = $v['name'];
$field .= ($field == '')?$v['dbfield']:','.$v['dbfield'];
}
$vo = M('Warehouse')->where("product_id=$product_id")->field($field)->find();
$result = array();
foreach ($data as $k=>$v) 
{
if($vo[$k] == '')$vo[$k]=0;
$result[] = array('name'=>$v,'qty'=>$vo[$k],'dbfield'=>$k);
}
return $result;
}
public function getSaleWarehouse()
{
$uid = session(C('USER_AUTH_KEY'));
$prefix = C( 'DB_PREFIX');
$B = $prefix.'warehouse_name B';
$vo = M('UserSalesWarehouse')->query("
						SELECT B.id,B.name FROM __TABLE__ A 
						LEFT JOIN $B ON A.warehouse_id=B.id 
						WHERE A.uid=$uid AND B.on_sale='1' AND B.status='1' ORDER BY B.seq ASC");
foreach ($vo as &$v)
{
$v['title'] = $v['name'];
$v['level'] = 0;
}
return $vo;
}
public function getUserStock()
{
$uid = session(C('USER_AUTH_KEY'));
$prefix = C( 'DB_PREFIX');
$B = $prefix.'warehouse_name B';
$vo = M('UserStock')->query("
						SELECT B.id,B.name FROM __TABLE__ A 
						LEFT JOIN $B ON A.warehouse_id=B.id 
						WHERE A.uid=$uid AND B.status='1' ORDER BY B.seq ASC");
foreach ($vo as &$v)
{
$v['title'] = $v['name'];
$v['level'] = 0;
}
return $vo;
}
public function getWarehouse ()
{
$vo = M('WarehouseName')->order('seq ASC')->select();
$ret = array();
foreach ($vo as &$v)
{
$v['title'] = $v['name'];
$v['level'] = 0;
$id = $v['id'];
$ret[$id] = $v;
}
return $ret;
}
}?>