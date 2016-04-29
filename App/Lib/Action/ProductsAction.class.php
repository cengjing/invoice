<?php

class ProductsAction extends GlobalAction 
{
public function index()
{
$this->_getProducts();
$this->setTitle('商品目录');
$this->display();
}
public function add()
{
$data['on_sale'] = 1;
$data['purchase'] = 1;
$data['tax'] = 1;
$this->assign('data',$data);
$this->setTitle('新增商品');
$this->display();
}
public function edit()
{
$id = intval($_REQUEST['id']);
if($id <= 0)
{
$this->error('错误的商品编号');
}
$data['on_sale'] = 1;
$data['purchase'] = 1;
$data['tax'] = 1;
$this->assign('data',$data);
$vo = D('Products')->getInfo($id);
$this->assign('vo',$vo['base']);
$this->setTitle('修改商品');
$this->display();
}
public function show()
{
$id = intval($_REQUEST['id']);
if($id<= 0)
{
$this->error('错误的商品编号');
}
$vo = M('Products')->getById ( $id );
$this->assign ( 'vo',$vo );
$category_id = $vo['category_id'];
$this->setTitle($vo['productname'].'_'.$vo['catno']);
$access = session('_header_access');
if(isset($access['WAREHOUSE']['INDEX'])){
$vo = D('Warehouse')->getStockQty($_REQUEST['id']);
foreach ($vo as $v)
{
$data = array();
$data['display'] 	= $v['name'];
$data['name'] 		= $v['dbfield'];
$data['width'] 		= 80;
$data['align'] 		= 'right';
$header[] 			= $data;
}
$this->assign('header',$header );
foreach ($vo as $v)
{
$arr[$v['dbfield']] = $v['qty'];
}
$qty[] = $arr;
$this->assign('inventoryQty',$qty );
}
if(isset($access['PURCHASE']['ADD']))
{
$A = $this->prefix .'salesorder_details A ';
$B = $this->prefix .'salesorder B';
$vo = M()->query("SELECT A.qty,A.deli_qty,A.sale_price,B.* FROM $A LEFT JOIN $B ON (A.order_id=B.id) WHERE deli_qty<qty AND B.status='3' AND A.product_id=$id ORDER BY A.id DESC LIMIT 10");
$this->assign('orderDetails',$vo);
$A = $this->prefix .'purchase_details A ';
$B = $this->prefix .'purchase B';
$vo = M()->query("SELECT A.qty,A.entry_qty,A.price,B.* FROM $A LEFT JOIN $B ON (A.purchase_id=B.id) WHERE entry_qty<qty AND B.status IN (1,2,3,4) AND A.product_id=$id ORDER BY A.id DESC LIMIT 10");
$this->assign('purchaseDetails',$vo);
}
$this->display();
}
public function insert()
{
$this->validate();
$_POST['on_sale'] = isset($_REQUEST['on_sale'])?1:0;
$_POST['serial'] = isset($_REQUEST['serial'])?1:0;
$_POST['purchase'] = isset($_REQUEST['purchase'])?1:0;
$model = D('Products');
if (false === $model->create ()) {
$this->ajaxReturn('',$model->getError (),0);
}
if(isset($_REQUEST['id']))
{
$id = intval($_REQUEST['id']);
$ret = $model->save();
if(!$ret)
{
$this->ajaxReturn('',$model->getError (),0);
}
}
else 
{
$id = $model->add ();
}
if ($id !== false) {
$result['id'] = $id;
$this->ajaxReturn ( $result,'',1);
}else {
$this->ajaxReturn ( '','新增失败!',0);
}
}
public function update()
{
$this->insert();
}
public function filter()
{
$_REQUEST['on_sale'] = 1;
$this->_getProducts();
$this->display();
}
private function _getProducts()
{
$ret = $this->getFilter('Products','A.');
$A = $this->prefix .'products A ';
$B = $this->prefix .'picklist_details B ';
$from 		= " FROM $A";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.title category $from LEFT JOIN $B ON (A.category_id=B.id) $where ORDER BY A.catno ASC";
$this->_pageList ( $countSQL,$pageSQL ,$ret['arr_sum']);
}
public function getById()
{
if(!$this->isAjax())return;
$model = M('Products');
$map['id'] = $_REQUEST['id'];
$productManage = false;
if($productManage == true){
$vo = $model->where($map)->field('id,productname,catno,category_id,description,ch_description,discount,unit,spec,unit_price')->find();
}else{
$vo = $model->where($map)->find();
}
$this->ajaxReturn($vo,'',1);
}
public function getDiscount()
{
if(!$this->isAjax())return;
$product_id = intval($_REQUEST['product_id']);
$company_id = intval($_REQUEST['company_id']);
$data = D('Products')->getDiscount($product_id,$company_id);
$ds = $data['ds'];
$price = $data['price'];
$data['sale_price'] = number_format($price,2,".",",");
$data['ds_price'] = number_format($price * (1 -$ds * 0.01),2,".",",");
$this->assign('data',$data);
$this->display('discount');
}
public function record()
{
$id = intval($_REQUEST['id']);
$this->assign('id',$id);
$ret = $this->getFilter('ProductsRecords','A.');
if(!empty($ret['where']))$where = ' AND '.$ret['where'];
$vo = M('Products')->getById($id);
$this->setTitle('存货帐_'.$vo['productname']);
$this->assign('productName',$vo['productname']);
$model = M();
$A = $this->prefix .'delivery A ';
$B = $this->prefix .'delivery_details B ';
$Sql = "SELECT A.id,A.cTime act_time,A.status,A.type,A.warehouse_id,A.uid,A.from_id,B.stock_qty,B.qty,B.order_id 
					FROM $A LEFT JOIN $B ON (A.id=B.delivery_id) WHERE B.product_id=$id AND A.status<>3 $where ORDER BY B.id ASC";
$vo1 = $model->query($Sql);
$D = D('Delivery');
foreach ($vo1 as &$v)
{
$v['account_type'] = '发货';
$v['qty'] = '-'.$v['qty'];
$v['status'] = $D->getStatus($v['status']);
$v['url_id'] = "<a href='".u('delivery/show',array('id'=>$v['id']))."' target='_blank'>".$v['id']."</a>";
switch ($v['type'])
{
case 1:
$v['from_id'] = "<a target='_blank' href='".u('salesorder/show',array('id'=>$v['order_id']))."'>".$v['order_id']."</a>";
break;
case 2:
$v['from_id'] = "<a target='_blank' href='".u('allot/show',array('id'=>$v['from_id']))."'>".$v['from_id']."</a>";
break;
case 3:
$v['from_id'] = "<a target='_blank' href='".u('stocktaking/show',array('id'=>$v['from_id']))."'>".$v['from_id']."</a>";
break;
}
$v['type'] = $D->getType($v['type']);
}
$A = $this->prefix .'entry A ';
$B = $this->prefix .'entry_details B ';
$Sql = "SELECT A.id,A.cTime,A.status,A.type,A.warehouse_id,A.uid,A.act_time,A.from_id,B.stock_qty,B.qty 
					FROM $A LEFT JOIN $B ON (A.id=B.entry_id) WHERE B.product_id=$id AND A.status NOT IN (3,4) $where";
$vo2 = $model->query($Sql);
$E = D('Entry');
foreach ($vo2 as &$v)
{
$v['account_type'] = '入库';
$v['qty'] = '+'.$v['qty'];
if($v['type'] == 2)
{
$v['act_time'] = $v['cTime'];
}
switch ($v['type'])
{
case 1:
$v['from_id'] = "<a target='_blank' href='".u('arrival/show',array('id'=>$v['from_id']))."'>".$v['from_id']."</a>";
break;
case 2:
$v['from_id'] = "<a target='_blank' href='".u('allot/show',array('id'=>$v['from_id']))."'>".$v['from_id']."</a>";
break;
case 3:
$v['from_id'] = "<a target='_blank' href='".u('returns/show',array('id'=>$v['from_id']))."'>".$v['from_id']."</a>";
break;
case 4:
$v['from_id'] = "<a target='_blank' href='".u('stocktaking/show',array('id'=>$v['from_id']))."'>".$v['from_id']."</a>";
break;
}
$v['type'] = $E->getType($v['type']);
$v['status'] = $E->getStatus($v['status']);
$v['url_id'] = "<a href='".u('stockin/show',array('id'=>$v['id']))."' target='_blank'>".$v['id']."</a>";
}
$ret = array_merge($vo2,$vo1);
$ret = list_sort_by($ret,'act_time','desc');
$this->assign('list',$ret);
$this->display();
}
public function getStockQty()
{
if(!$this->isAjax())return;
$id = intval($_REQUEST['id']);
$this->assign('list',D('Warehouse')->getStockQty($id));
$this->display('qty');
}
public function editAll()
{
$this->setTitle('商品批量修改');
$items = $_REQUEST['items'];
if(!empty($items)){
$map['id'] = array('IN',$items);
$vo = M('Products')->where($map)->field('productname,catno')->select();
$this->assign('list',$vo);
$this->assign('items',$items);
}
$this->display();
}
public function updateAll()
{
$filter_category_id 	= $_REQUEST['filter_category_id'];
$filter_brand_id 		= $_REQUEST['filter_brand_id'];
$items 					= $_REQUEST['items'];
if($items == ''){
if(!empty($filter_category_id)) 
$map['category_id'] = $filter_category_id;
if(!empty($filter_brand_id)) 
$map['brand_id'] = $filter_brand_id;
}else{
$map['id'] = array('IN',$items);
}
if(count($map) == 0){
$this->ajaxReturn ( '','错误的筛选条件，必须选择至少一个条件!',0);
}
$vo = M('Fields')->where(array('module'=>'ProductsCorrectional','edit'=>1))->field('field,type')->select();
foreach ($vo as $v)
{
if (isset($_REQUEST[$v['field']]) &&!empty($_REQUEST[$v['field']]))
{
if( 'on_sale'== $v['field'] &&$_REQUEST['on_sale'] == 2)$_REQUEST['on_sale'] = 0;
if( 'purchase'== $v['field'] &&$_REQUEST['purchase'] == 2)$_REQUEST['purchase'] = 0;
$data[$v['field']] = $_REQUEST[$v['field']];
}
}
if(count($data) == 0){
$this->ajaxReturn ( '','错误的修改条件，必须选择至少一个条件!',0);
}
$ret = M('Products')->where($map)->save($data);
$this->ajaxReturn ( '','完成批量修改!',1);
}
public function import()
{
$this->setTitle('导入商品');
$data['on_sale'] = 1;
$data['purchase'] = 1;
$this->assign('data',$data);
$this->display('import');
}
public function doimport()
{
if(empty($_FILES))
{
$this->error('必须选择上传的CVS数据库文件');
}
$this->validate('ProductsImport');
$category_id = intval($_REQUEST['category_id']);
$vender_id = intval($_REQUEST['vender_id']);
$cTime = time();
$on_sale = isset($_REQUEST['on_sale'])?1:0;
$purchase = isset($_REQUEST['purchase'])?1:0;
$model = M('Products');;
import('ORG.Util.String');
$file = fopen($_FILES['data']['tmp_name'],"r");
$model->startTrans();
while(!feof($file))
{
$row = fgetcsv($file);
$data = array();
if($row[0] != ''&&$row[1] != '')
{
$data['catno'] = String::autoCharset($row[0]);
$data['productname'] = String::autoCharset($row[1]);
$vo = $model->where(array('catno'=>$data['catno']))->field('catno')->find();
if($vo)
{
$model->rollback();
fclose($file);
$this->error ( '导入商品"'.$data['catno'].'", "'.$data['productname'].'"已存在数据库中，商品编码不能重复。');
}
$data['on_sale'] = $on_sale;
$data['purchase'] = $purchase;
$data['cTime'] = $cTime;
$data['brand_id'] = $_REQUEST['brand_id'];
$data['category_id'] = $category_id;
$data['vender_id'] = $vender_id;
$data['unit_price'] = String::autoCharset($row[2]);
$data['description'] = String::autoCharset($row[4]);
$data['ch_description'] = String::autoCharset($row[3]);
$ret = $model->add($data);
if($ret == false)
{
$model->rollback();
fclose($file);
$this->error ( '保存记录'.$row[0].','.$row[1].'失败!');
}
}
}
$model->commit();
fclose($file);
$this->success('成功批量导入商品。');
}
}
?>