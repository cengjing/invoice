<?php

class StocktakingAction extends WorkflowAction 
{
public function index()
{
$this->assign('title','库存盘点');
$ret = $this->getFilter('Stocktaking','A.');
$A = $this->prefix .'stocktaking A ';
$from 		= " FROM $A	";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*) $from $where";
$pageSQL 	= " SELECT A.* $from 
							$where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL );
$this->display();
}
public function add()
{
$this->setTitle('新建盘点');
$this->display();
}
public function edit() 
{
$this->_before_show();
$this->setTitle('修改盘点');
$this->display ();
}
public function toExcel()
{
$warehouse_id = intval($_REQUEST['warehouse_id']);
$dbfield = getWarehouseInfo($warehouse_id);
$warehouseName = $dbfield['name'];
$dbfield = $dbfield['dbfield'];
$A = $this->prefix.'warehouse A';
$B = $this->prefix.'products B';
$vo = M()->query("SELECT A.$dbfield,B.productname,B.catno FROM $A LEFT JOIN $B ON A.product_id=B.id WHERE A.$dbfield>0");
error_reporting(E_ALL);
date_default_timezone_set('Asia/Beijing');
vendor('PHPExcel/PHPExcel');
$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("SibohCRM")
->setLastModifiedBy("SibohCRM")
->setTitle($warehouseName.'-库存盘点-'.date('Y:m:d'))
->setSubject("Office 2007 XLSX Test Document")
->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
->setKeywords("office 2007 openxml php")
->setCategory("Test result file");
$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getActiveSheet()->setTitle('库存盘点记录');
$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth('35pt');
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('35pt');
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('35pt');
$objPHPExcel->getActiveSheet()->setCellValue("A1",'商品编号')->getStyle('A1')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->setCellValue("B1",'商品货号')->getStyle('B1')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->setCellValue("C1",'库存数量')->getStyle('C1')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('C1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
foreach ($vo as $k=>$v){
$i = $k +2;
$objPHPExcel->getActiveSheet()->setCellValue("A$i",$v['productname']);
$objPHPExcel->getActiveSheet()->setCellValue("B$i",$v['catno'])->getStyle("B$i")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
$objPHPExcel->getActiveSheet()->setCellValue("C$i",$v[$dbfield]);
}
$objPHPExcel->setActiveSheetIndex(0);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'HTML');
$objWriter->save('php://output');
exit;
}
public function insert()
{
$this->validate();
$warehouse_id = $_REQUEST['warehouse_id'];
$vo = M('Stocktaking')->where("warehouse_id=$warehouse_id AND (status=3 OR status=2)")->select();
$warehouseName = getWarehouseName($warehouse_id);
if(count($vo)>0){
$this->ajaxReturn('','当前'.$warehouseName.'有未完成的盘点记录，不能再次提交审核。',0);
}
$_POST['uid'] = $this->uid;
$id = $this->save('Stocktaking');
if ($id !== false) {
$model = M('StocktakingDetails');
$model->where(array('st_id'=>$id))->delete();
foreach ($_REQUEST['pid'] as $k=>$v){
$data = array();
$data['st_id'] = $id;
$data['product_id'] = $v;
$data['qty'] = $_REQUEST['qty'][$k];
$model->add($data);
}
$result['id'] = $id;
D('Record')->insert($id,'保存盘点记录。',false,$this->getActionName());
$this->ajaxReturn($result,'',1);
}else {
$this->error ( '新增失败!');
}
}
public function update()
{
$this->insert();
}
public function _before_show()
{
parent::_before_show();
$id = intval($_REQUEST['id']);
$model = D('Stocktaking');
$vo = $model->where("id=$id")->find();
$this->assign('vo',$vo);
$details = $model->getDetails($id,$vo['warehouse_id']);
$this->assign('details',$details);
}
public function show()
{
$vo = $this->__get('vo');
$this->assign('title','盘点记录-'.$vo['id']);
$this->display();
}
public function afterApprove()
{
$id = intval($_REQUEST['id']);
$S = D('Stocktaking');
$vo = $S->where("id=$id")->find();
$uid = $vo['uid'];
$warehouse_id = $vo['warehouse_id'];
$tmp = getWarehouseInfo($warehouse_id);
$dbfield = $tmp['dbfield'];
$details = $S->getDetails($id,$warehouse_id);
foreach ($details as $v)
{
if($v['more'] >0)
{
$data = array();
$data['product_id'] = $v['product_id'];
$data['qty'] = $v['more'];
$more[] = $data;
}elseif ($v['less'] >0)
{
$data = array();
$data['product_id'] = $v['product_id'];
$data['qty'] = $v['less'];
$less[] = $data;
}
}
if(count($more) >0)
{
$data					= 	array();
$data['type']			=	'4';
$data['from_id']		=	$id;
$data['uid']			=	$uid;
$data['warehouse_id']	=	$warehouse_id;
$data['status']			=	1;
$data['cTime']			=	time();
$E = D('Entry');
$entry_id = $E->add($data);
$entry_id = $E->saveId($entry_id);
$ED = M('EntryDetails');
foreach ($more as $v)
{
$data				= 	array();
$data['entry_id']	=	$entry_id;
$data['product_id']	=	$v['product_id'];
$data['qty']		=	$v['qty'];
$ED->add($data);
}
D('Record')->insert($id,"生成盘盈入库，单据号：$entry_id",false,$this->getActionName());
$data 					= 	array();
$data['id']				=	$id;
$data['entry_id']		=	$entry_id;
$S->save($data);
}
if(count($less) >0)
{
$data					= 	array();
$data['uid']			=	$uid;
$data['act_uid']		=	$this->uid;
$data['type']			=	'3';
$data['from_id']		=	$id;
$data['warehouse_id']	=	$warehouse_id;
$data['status']			=	2;
$data['cTime']			=	time();
$data['act_time']		=	time();
$D = D('Delivery');
$delivery_id = $D->add($data);
$delivery_id = $D->saveId($delivery_id);
$DD = M('DeliveryDetails');
$WH = D('Warehouse');
foreach ($less as $v)
{
$warehouseQty = $WH->Dec($dbfield,$v['product_id'],$v['qty']);
$data					= 	array();
$data['delivery_id']	=	$delivery_id;
$data['product_id']		=	$v['product_id'];
$data['qty']			=	$v['qty'];
$data['stock_qty']		=	$warehouseQty;
$DD->add($data);
}
D('Record')->insert($id,"生成盘亏出库，单据号：$delivery_id",false,$this->getActionName());
$data 					= 	array();
$data['id']				=	$id;
$data['delivery_id']	=	$delivery_id;
$S->save($data);
}
$S->checkDone($id);
}
public function delete()
{
parent::delete();
}
}
?>