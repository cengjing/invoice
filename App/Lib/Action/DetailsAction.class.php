<?php

class DetailsAction extends GlobalAction
{
public function index()
{
return;
}
public function add()
{
return;
}
public function edit()
{
return;
}
public function insert()
{
return;
}
public function sod()
{
$this->setTitle('销售明细表');
$this->assign('module','Sod');
$this->display('index');
}
public function pod()
{
$this->setTitle('采购明细表');
$this->assign('module','Pod');
$this->display('index');
}
public function sodStat()
{
$ret = $this->getFilter('Sod','A.',false,false);
$A = $this->prefix .'salesorder A ';
$B = $this->prefix .'user B ';
$G = $this->prefix .'salesorder_details G';
$C = $this->prefix .'products C';
$D = $this->prefix .'picklist_details D';
$E = $this->prefix .'picklist_details E';
$from 		= " FROM $G 
						LEFT JOIN $A ON G.order_id=A.id
						LEFT JOIN $C ON C.id=G.product_id
						LEFT JOIN $B ON A.assignto=B.id
						LEFT JOIN $D ON C.category_id=D.id
						LEFT JOIN $E ON C.brand_id=E.id
						";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$g = $_REQUEST['group'];
if ($g == 1){
$group	= "";
$order = "ORDER BY A.id DESC";
$page = 'data_table';
$field = "";
}elseif ($g == 2){
$group	= "GROUP BY C.category_id ";
$field = " SUM(G.sale_price*G.qty) s, ";
$page = 'pie_chart';
}elseif ($g == 3){
$group	= "GROUP BY C.brand_id ";
$field = " SUM(G.sale_price*G.qty) s, ";
$page = 'pie_chart';
}
$Sql	= " SELECT 
						$field
						A.id,A.cTime,A.cTime,A.ship_completion_date,A.invoice_completion_date,A.payment_completion_date,A.status,
						B.name,
						C.catno,
						C.productname,
						C.category_id,
						C.brand_id,
						D.title category,
						E.title brand,
						G.sale_price,
						G.qty
						$from $where $group $order";
$vo = M()->query($Sql);
if( $g == 1 ){
$h[] = array('display'=>'订单编号','name'=>'id','width'=>'80','align'=>'left','url'=>'__APP__/salesorder/show/id/','url_id'=>'id');
$h[] = array('display'=>'日期','name'=>'cTime','width'=>'70','align'=>'left','func'=>'getTime');
$h[] = array('display'=>'商品编号','name'=>'catno','width'=>'100','align'=>'left');
$h[] = array('display'=>'商品名称','name'=>'productname','width'=>'100','align'=>'left');
$h[] = array('display'=>'发货完成','name'=>'ship_completion_date','width'=>'70','align'=>'left','func'=>'getTime');
$h[] = array('display'=>'发票完成','name'=>'invoice_completion_date','width'=>'70','align'=>'left','func'=>'getTime');
$h[] = array('display'=>'收款完成','name'=>'payment_completion_date','width'=>'70','align'=>'left','func'=>'getTime');
$h[] = array('display'=>'售价','name'=>'sale_price','width'=>'80','align'=>'right','func'=>'formatCurrency');
$h[] = array('display'=>'数量','name'=>'qty','width'=>'60','align'=>'right');
$h[] = array('display'=>'小计','name'=>'s_price','width'=>'100','align'=>'right','func'=>'formatCurrency');
$h[] = array('display'=>'分类','name'=>'category','width'=>'60','align'=>'left');
$h[] = array('display'=>'品牌','name'=>'brand','width'=>'60','align'=>'left');
$sum = 0;
foreach ($vo as &$val)
{
$val['s_price'] = $val['sale_price'] * $val['qty'];
$sum += $val['sale_price'] * $val['qty'];
}
$this->assign('header',$h);
$this->assign('list',$vo);
$this->assign('sum',array('sum_s_price'=>formatCurrency($sum)));
}elseif ( $g == 2 ){
$column1 = "'string','商品分类'";
$column2 = "'number','金额'";
foreach($vo as $v){
$data .=($data==''?'':',')."['".$v['category']."',".$v['s']."]";
}
$this->assign('column1',$column1);
$this->assign('column2',$column2);
$this->assign('chart_title','商品分类统计');
$this->assign('data',$data);
}elseif ( $g == 3 ){
$column1 = "'string','品牌'";
$column2 = "'number','金额'";
foreach($vo as $v){
$data .=($data==''?'':',')."['".$v['brand']."',".$v['s']."]";
}
$this->assign('column1',$column1);
$this->assign('column2',$column2);
$this->assign('chart_title','品牌统计');
$this->assign('data',$data);
}
$this->display($page);
}
public function podStat()
{
$ret = $this->getFilter('Pod','A.',false,false);
$A = $this->prefix .'purchase A ';
$B = $this->prefix .'vender B ';
$C = $this->prefix .'purchase_details C ';
$D = $this->prefix .'products D';
$E = $this->prefix .'picklist_details E';
$F = $this->prefix .'picklist_details F';
$from 		= " FROM $C
						LEFT JOIN $A ON C.purchase_id=A.id
						LEFT JOIN $B ON A.vender_id=B.id
						LEFT JOIN $D ON C.product_id=D.id
						LEFT JOIN $E ON D.category_id=E.id
						LEFT JOIN $F ON D.brand_id=F.id
						";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$g = $_REQUEST['group'];
if ($g == 1){
$group	= "";
$order = "ORDER BY A.id DESC";
$page = 'data_table';
$field = "";
}elseif ($g == 2){
$group	= "GROUP BY D.category_id ";
$field = " SUM(C.price*C.qty) s, ";
$page = 'pie_chart';
}elseif ($g == 3){
$group	= "GROUP BY D.brand_id ";
$field = " SUM(C.price*C.qty) s, ";
$page = 'pie_chart';
}
$Sql	= " SELECT
					$field
					A.id,A.cTime,A.ship_completion_date,A.invoice_completion_date,A.payment_completion_date,A.status,
					B.company,
					C.price,C.qty,C.entry_qty,C.arr_qty,
					D.catno,
					D.productname,
					D.category_id,
					D.brand_id,
					E.title category,
					F.title brand
					$from $where $group $order";
$vo = M()->query($Sql);
if( $g == 1 ){
$h[] = array('display'=>'采购单号','name'=>'id','width'=>'80','align'=>'left','url'=>'__APP__/purchase/show/id/','url_id'=>'id');
$h[] = array('display'=>'日期','name'=>'cTime','width'=>'70','align'=>'left','func'=>'getTime');
$h[] = array('display'=>'供货商','name'=>'company','width'=>'100','align'=>'left');
$h[] = array('display'=>'商品编号','name'=>'catno','width'=>'100','align'=>'left');
$h[] = array('display'=>'商品名称','name'=>'productname','width'=>'100','align'=>'left');
$h[] = array('display'=>'到货完成','name'=>'ship_completion_date','width'=>'70','align'=>'left','func'=>'getTime');
$h[] = array('display'=>'发票完成','name'=>'invoice_completion_date','width'=>'70','align'=>'left','func'=>'getTime');
$h[] = array('display'=>'付款完成','name'=>'payment_completion_date','width'=>'70','align'=>'left','func'=>'getTime');
$h[] = array('display'=>'采购价格','name'=>'price','width'=>'80','align'=>'right','func'=>'formatCurrency');
$h[] = array('display'=>'数量','name'=>'qty','width'=>'60','align'=>'right');
$h[] = array('display'=>'小计','name'=>'s_price','width'=>'100','align'=>'right','func'=>'formatCurrency');
$h[] = array('display'=>'到货数量','name'=>'arr_qty','width'=>'60','align'=>'right');
$h[] = array('display'=>'入库数量','name'=>'entry_qty','width'=>'60','align'=>'right');
$h[] = array('display'=>'分类','name'=>'category','width'=>'100','align'=>'left');
$h[] = array('display'=>'品牌','name'=>'brand','width'=>'60','align'=>'left');
$sum = 0;
foreach ($vo as &$val)
{
$val['s_price'] = $val['price'] * $val['qty'];
$sum += $val['price'] * $val['qty'];
}
$this->assign('header',$h);
$this->assign('list',$vo);
$this->assign('sum',array('sum_s_price'=>formatCurrency($sum)));
}elseif ( $g == 2 ){
$column1 = "'string','商品分类'";
$column2 = "'number','金额'";
foreach($vo as $v){
$data .=($data==''?'':',')."['".$v['category']."',".$v['s']."]";
}
$this->assign('column1',$column1);
$this->assign('column2',$column2);
$this->assign('chart_title','商品分类统计');
$this->assign('data',$data);
}elseif ( $g == 3 ){
$column1 = "'string','品牌'";
$column2 = "'number','金额'";
foreach($vo as $v){
$data .=($data==''?'':',')."['".$v['brand']."',".$v['s']."]";
}
$this->assign('column1',$column1);
$this->assign('column2',$column2);
$this->assign('chart_title','品牌统计');
$this->assign('data',$data);
}
$this->display($page);
}
}
?>