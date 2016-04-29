<?php

class StockoutAction extends GlobalAction 
{
public function index()
{
$this->setTitle('发货单列表');
$warehouse = D('Warehouse')->getUserStock();
$ws = '';
foreach ($warehouse as $val)
{
$ws .= (($ws == '') ?'': ',') .$val['id'];
}
$ret = $this->getFilter('Stockout','A.',true);
if(!isset($_REQUEST['status']))
{
$status = ' AND A.status=1';
}
$A = $this->prefix.'delivery A';
$from 		= " FROM $A ";
$where 		= " WHERE A.warehouse_id IN($ws) ".($ret['where'] != ''?" AND ".$ret['where']:'') .$status;
$countSql 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSql 	= " SELECT * $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSql,$pageSql,$ret['arr_sum']);
$this->display();
}
public function show()
{
$id = intval($_REQUEST['id']);
$model = D('Stockout');
$vo = $model->getPerm($id);
if($vo == false)
{
$this->error('您没有操作这个仓库的权限');
}
$this->setTitle('发货操作_'.$vo['id']);
$this->assign('vo',$vo);
$details = $model->getDetails($id);
$this->assign('details',$details);
$this->display();
}
public function insert()
{
$this->validate('Stockout','edit');
$id = intval($_REQUEST['id']);
$model = D('Stockout');
$vo = $model->getPerm($id);
if($vo == false)
{
$this->ajaxReturn('','您没有操作这个仓库的权限',0);
}
if($vo['status'] == 1)
{
$data['act_uid']		= $this->uid;
$data['act_time']	 	= time();
$data['status']			= 2;
}
if ($vo['status'] == 3)
{
$ret['id'] = $id;
$this->ajaxReturn($ret,'发货单已经被删除，不能再发货。',0);
}
$data['delivery_way']	= $_POST['delivery_way'];
$data['tracking_No']	= $_POST['tracking_No'];
$data['id']				= $id;
M('Delivery')->save($data);
if($vo['type'] == 1)
{
$orders = M('DeliveryDetails')->where(array('delivery_id'=>$id))->field('order_id')->group('order_id')->select();
foreach ($orders as $v)
{
if($vo['status'] == 1){
D('Record')->insert($v['order_id'],"发货单 $id 完成发货",true,'Salesorder');
}else{
D('Record')->insert($v['order_id'],"修改发货单 $id",false,'Salesorder');
}
}
}
if($vo['type'] == 3)
{
$vo = M('Stocktaking')->where(array('delivery_id'=>$id))->find();
if($vo)
{
D('Stocktaking')->checkDone($vo['id']);
}
}
$result['id'] = $id;
$this->ajaxReturn($result,'',1);
}
public function report()
{
$module = 'Delivery';
$id		= intval($_REQUEST['id']);
$map['module'] = $module;
$vo = M('Modules')->where($map)->find();
if (!$vo) 
{
return ;
}
$this->assign('module',$module);
$this->assign('detailsModule',$vo['detailsTable']);
$this->assign('moduleName',$vo['title']);
$this->assign('title',$vo['title'].' - '.$id);
$model = D('Stockout');
$vo = $model->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->assign('vo',$vo);
$details = $model->getDetails($id);
$this->assign('details',$details);
$this->display('Public:report');
}
public function express()
{
$id = intval($_REQUEST['id']);
$delivery_way = intval($_REQUEST['delivery_way']);
$vo = D('Stockout')->where(array('id'=>$id))->find();
$this->assign('to',$vo['name']);
$this->assign('to_tel',$vo['mobilephone']);
$this->assign('to_company',$vo['company']);
$this->assign('to_addr',$vo['address']);
$this->assign('to_postal',$vo['postalcode']);
$model = M ( 'PrintTempl');
if(!empty($vo['delivery_way']))
$delivery_way = $vo['delivery_way'];
$vo = $model->where(array('express_id'=>$delivery_way))->find();
$this->assign ( 'vo',$vo );
$map ['module'] = 'Printer';
$map ['recordId'] = $vo['id'];
$vo = M ( 'Attach')->where ( $map )->find ();
if ($vo != null) {
$tmp = explode('.',$vo ['savepath']);
$bgImage = __ROOT__ .$tmp[1] .$vo ['savename'];
$this->assign ( 'bgImage',$bgImage );
}
$vo = M('User')->where(array('id'=>$this->uid))->find();
$userName = $vo['name'];
$department_id = $vo['department_id'];
if($department_id >0){
$d = M('Department')->where(array('id'=>$department_id))->find();
$this->assign('from_tel',$d['phone']);
$this->assign('from_addr',$d['address']);
$this->assign('from_postal',$d['postalcode']);
}
$this->assign('form',$userName);
import("ORG.Util.Date");
$date = new Date(time());
$this->assign('from_date',$date->year.'-'.$date->month.'-'.$date->day);
$vo = M('config')->where("name='site_company'")->find();
$this->assign('from_comp',$vo['value']);
$this->setTitle('打印快递单');
$this->display();
}
public function doBack()
{
$id	= intval($_REQUEST['id']);
$model = D('Delivery');
$map['id'] = $id;
$map['status'] = 1;
$model->save($map);
$orders = M('DeliveryDetails')->where(array('delivery_id'=>$id))->field('order_id')->group('order_id')->select();
foreach ($orders as $v)
{
D('Record')->insert($v['order_id'],"退回发货单 $id",false,'Salesorder');
}
$this->success('成功退回发货单据。');
}
}?>