<?php

class WarehouseAction extends GlobalAction 
{
public function index()
{
$this->setTitle('库存列表');
if (!empty($_REQUEST['productname']))
{
$map[] = " productname LIKE '%".$_REQUEST['productname']."%' ";
}
if (!empty($_REQUEST['catno']))
{
$map[] = " catno='".$_REQUEST['catno']."' ";
}
if (!empty($_REQUEST['warehouse_id']))
{
$w = getWarehouseInfo($_REQUEST['warehouse_id']);
$dbfield = $w['dbfield'];
$map[] = " $dbfield>0 ";
}
if (!empty($_REQUEST['category_id']))
{
$map[] = " category_id= ".$_REQUEST['category_id'];
}
if (!empty($_REQUEST['on_sale']))
{
if($_REQUEST['on_sale'] == -1)$_REQUEST['on_sale'] = 0;
$map[] = " B.on_sale= ".$_REQUEST['on_sale'];
}
$ret = '';
if(sizeof($map)>0){
foreach ($map as $v){
$ret .= ($ret == ''?'':' AND ').$v;
}
}
$A = $this->prefix .'warehouse A';
$B = $this->prefix .'products B';
$from 		= " FROM $A LEFT JOIN $B ON A.product_id=B.id";
$where 		= ($ret==''?'':' WHERE '.$ret);
$countSQL 	= "SELECT COUNT(*) $from $where";
$pageSQL 	= "SELECT A.*,B.productname,B.catno,B.id pid $from $where";
$this->_pageList ( $countSQL,$pageSQL );
$header = array(
array('display'=>'商品名称','name'=>'productname','width'=>'150','align'=>'left'),
array('display'=>'商品编号','name'=>'catno','width'=>'100','align'=>'left','url'=>'__APP__/products/show/id/','url_id'=>'pid'),
);
if (isset($dbfield))
{
unset($map);
$map['dbfield'] = $dbfield;
$vo = M('WarehouseName')->where($map)->order('seq ASC')->select();
}else{
$vo = M('WarehouseName')->order('seq ASC')->select();
}
foreach ($vo as $v)
{
$data = array();
$data['display'] 	= $v['name'];
$data['name'] 		= $v['dbfield'];
$data['width'] 		= 80;
$data['align'] 		= 'right';
$header[] 			= $data;
}
$this->assign('header',$header);
$this->display();
}
public function safe()
{
$this->setTitle('最小库存');
$field = '';
$vo = M('WarehouseName')->where('on_sale=1')->field('dbfield')->select();
foreach ($vo as $val){
$field .= (($field=='')?'':'+').$val['dbfield'];
}
$A = $this->prefix.'warehouse A';
$B = $this->prefix.'products B';
$C = $this->prefix.'picklist_details C';
$SQL = "SELECT $field qty,B.min_stock_qty,B.productname,B.catno,B.id,C.title category FROM $B 
				LEFT JOIN $A ON (A.product_id=B.id) 
				LEFT JOIN $C ON (B.category_id=C.id)
				WHERE B.min_stock_qty>0 AND B.on_sale=1 ORDER BY B.category_id ASC";
$vo = M()->query($SQL);
foreach ($vo as $val)
{
if(floatval($val['qty'])<=floatval($val['min_stock_qty']))
{
$val['qty'] = number_format(floatval($val['qty']),2,".","");;
$list[] = $val;
}
}
$this->assign('list',$list);
$this->display('safe');
}
}?>