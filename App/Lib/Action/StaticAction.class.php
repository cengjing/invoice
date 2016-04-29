<?php

class StaticAction extends GlobalAction 
{
private $type = array('salesorder');
public function index()
{
$this->setTitle('统计表');
$t = strtolower( $_REQUEST['t'] );
if(empty($t)) $t = 'salesorder';
if(!in_array($t,$this->type)) $this->error('错误的统计类型');
$this->assign('module',$t);
$this->display();
}
public function sod()
{
$this->setTitle('销售订单统计');
$this->assign('module','salesorder');
$this->display('index');
}
public function pod()
{
$this->setTitle('采购订单统计');
$this->assign('module','purchase');
$this->display('index');
}
public function wh()
{
$this->setTitle('库存统计');
$this->assign('module','warehouse');
$this->display('wh');
}
public function sodStat()
{
set_time_limit(0);
$ret = $this->getFilter('Salesorder','A.',false,false);
$A = $this->prefix .'salesorder A ';
$B = $this->prefix .'user B ';
$C = $this->prefix .'department C';
$D = $this->prefix .'accounts D ';
$E = $this->prefix .'contacts E ';
$F = $this->prefix .'network F ';
$from 		= " FROM $A 
						LEFT JOIN $B ON A.assignto=B.id
						LEFT JOIN $C ON B.department_id=C.id
						LEFT JOIN $D ON A.company_id=D.id
						LEFT JOIN $E ON A.contact_id=E.id
						LEFT JOIN $F ON D.province=F.id
						";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$f = $_REQUEST['field'];
if ($f == 1){
$field	= " SUM(sum) s, ";
$order = " ORDER BY s DESC";
}elseif ($f == 2){
$field	= " SUM(total) t, ";
$order = " ORDER BY t DESC";
}elseif ($f == 3){
$field	= " COUNT(*) c, ";
$order = " ORDER BY c DESC";
}else {
$field	= " SUM(sum) s,SUM(total) t, ";
$order = " ORDER BY s DESC";
}
$g = $_REQUEST['group'];
if ($g == 1){
$group	= "GROUP BY FROM_UNIXTIME(A.cTime,'%y/%m') ";
$page = 'combo_chart';
$order = '';
}elseif ($g == 2){
$group	= "GROUP BY A.company_id ";
$page = 'data_table';
}elseif ($g == 3){
$group	= "GROUP BY A.assignto ";
$page = 'data_table';
}elseif ($g == 4){
$group	= "GROUP BY B.department_id";
$page = 'pie_chart';
}else{
$group	= "GROUP BY D.province";
$page = 'pie_chart';
}
$Sql	= " SELECT 
							$field
							B.name,
							C.title department,
							D.company,
							E.name contact,
							F.title province,
							FROM_UNIXTIME(A.cTime,'%y/%m') m 
						$from $where $group $order";
$vo = M()->query($Sql);
if ($g == 1){
$header = "['月份','订单金额','总金额']";
$vAxis = '金额';
if ($f == 1){
$header = "['月份','订单金额']";
}elseif ($f == 2){
$header = "['月份','总金额']";
}elseif ($f == 3){
$header = "['月份','订单数量']";
$vAxis = '订单数量';
}else {
$header = "['月份','订单金额','总金额']";
}
foreach($vo as $v){
if ($f == 1){
$data .= ",['".$v['m'] ."',".$v['s'] ."]";
}elseif ($f == 2){
$data .= ",['".$v['m'] ."',".$v['t'] ."]";
}elseif ($f == 3){
$data .= ",['".$v['m'] ."',".$v['c'] ."]";
}else {
$data .= ",['".$v['m'] ."',".$v['s'] .",".$v['t'] ."]";
}
}
$this->assign('hAxis','月份');
$this->assign('vAxis',$vAxis);
$this->assign('chart_title','月度销售统计');
}elseif ( $g == 2 ){
$h[] = array('display'=>'客户名称','name'=>'company','width'=>'300','align'=>'left');
if ($f == 1){
$h[] = array('display'=>'订单金额','name'=>'s','width'=>'100','align'=>'right');
}elseif ($f == 2){
$h[] = array('display'=>'总金额','name'=>'t','width'=>'100','align'=>'right');
}elseif ($f == 3){
$h[] = array('display'=>'订单数量','name'=>'c','width'=>'100','align'=>'right');
}else {
$h[] = array('display'=>'订单金额','name'=>'s','width'=>'100','align'=>'right');
}
$this->assign('header',$h);
$this->assign('list',$vo);
}elseif ( $g == 3 ){
$h[] = array('display'=>'业务员','name'=>'name','width'=>'100','align'=>'left');
if ($f == 1){
$h[] = array('display'=>'订单金额','name'=>'s','width'=>'100','align'=>'right');
}elseif ($f == 2){
$h[] = array('display'=>'总金额','name'=>'t','width'=>'100','align'=>'right');
}elseif ($f == 3){
$h[] = array('display'=>'订单数量','name'=>'c','width'=>'100','align'=>'right');
}else {
$h[] = array('display'=>'订单金额','name'=>'s','width'=>'100','align'=>'right');
}
$this->assign('header',$h);
$this->assign('list',$vo);
}elseif ( $g == 4 ){
$column1 = "'string','部门'";
if ($f == 1){
$column2 = "'number','订单金额'";
$c = 's';
}elseif ($f == 2){
$column2 = "'number','总金额'";
$c = 't';
}elseif ($f == 3){
$column2 = "'number','订单数量'";
$c = 'c';
}else {
$column2 = "'number','订单金额'";
$c = 's';
}
foreach($vo as $v){
$data .=($data==''?'':',')."['".$v['department']."',".$v[$c]."]";
}
$this->assign('column1',$column1);
$this->assign('column2',$column2);
$this->assign('chart_title','部门销售统计');
}elseif ( $g == 5 ){
$column1 = "'string','省份'";
if ($f == 1){
$column2 = "'number','订单金额'";
$c = 's';
}elseif ($f == 2){
$column2 = "'number','总金额'";
$c = 't';
}elseif ($f == 3){
$column2 = "'number','订单数量'";
$c = 'c';
}else {
$column2 = "'number','订单金额'";
$c = 's';
}
foreach($vo as $v){
$data .=($data==''?'':',')."['".$v['province']."',".$v[$c]."]";
}
$this->assign('column1',$column1);
$this->assign('column2',$column2);
$this->assign('chart_title','地区销售统计');
}
$this->assign('data',$header.$data);
$this->assign('noResult',empty($data)?1:0);
$this->display($page);
}
public function podStat()
{
set_time_limit(0);
$ret = $this->getFilter('Purchase','A.',false,false);
$A = $this->prefix .'purchase A ';
$B = $this->prefix .'vender B ';
$from 		= " FROM $A
						LEFT JOIN $B ON A.vender_id=B.id
						";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$f = $_REQUEST['field'];
if ($f == 1){
$field	= " SUM(sum) s, ";
$order = " ORDER BY s DESC";
}elseif ($f == 2){
$field	= " SUM(amount) t, ";
$order = " ORDER BY t DESC";
}elseif ($f == 3){
$field	= " COUNT(*) c, ";
$order = " ORDER BY c DESC";
}else {
$field	= " SUM(sum) s,SUM(amount) t, ";
$order = " ORDER BY s DESC";
}
$g = $_REQUEST['group'];
if ($g == 1){
$group	= "GROUP BY FROM_UNIXTIME(A.cTime,'%y/%m') ";
$page = 'combo_chart';
$order = '';
}elseif ($g == 2){
$group	= "GROUP BY A.vender_id ";
$page = 'data_table';
}
$Sql	= " SELECT
					$field
					B.company,
					FROM_UNIXTIME(A.cTime,'%y/%m') m
					$from $where $group $order";
$vo = M()->query($Sql);
if ($g == 1)
{
$header = "['月份','订单金额','总金额']";
$vAxis = '金额';
if ($f == 1){
$header = "['月份','订单金额']";
}elseif ($f == 2){
$header = "['月份','总金额']";
}elseif ($f == 3){
$header = "['月份','订单数量']";
$vAxis = '订单数量';
}else {
$header = "['月份','订单金额','总金额']";
}
foreach($vo as $v){
if ($f == 1){
$data .= ",['".$v['m'] ."',".$v['s'] ."]";
}elseif ($f == 2){
$data .= ",['".$v['m'] ."',".$v['t'] ."]";
}elseif ($f == 3){
$data .= ",['".$v['m'] ."',".$v['c'] ."]";
}else {
$data .= ",['".$v['m'] ."',".$v['s'] .",".$v['t'] ."]";
}
}
$this->assign('hAxis','月份');
$this->assign('vAxis',$vAxis);
$this->assign('chart_title','月度销售统计');
}elseif ( $g == 2 ){
$h[] = array('display'=>'客户名称','name'=>'company','width'=>'300','align'=>'left');
if ($f == 1){
$h[] = array('display'=>'订单金额','name'=>'s','width'=>'100','align'=>'right');
}elseif ($f == 2){
$h[] = array('display'=>'总金额','name'=>'t','width'=>'100','align'=>'right');
}elseif ($f == 3){
$h[] = array('display'=>'订单数量','name'=>'c','width'=>'100','align'=>'right');
}else {
$h[] = array('display'=>'订单金额','name'=>'s','width'=>'100','align'=>'right');
}
$this->assign('header',$h);
$this->assign('list',$vo);
}
$this->assign('data',$header.$data);
$this->assign('noResult',empty($data)?1:0);
$this->display($page);
}
public function whStat()
{
set_time_limit(0);
$category_id = intval($_REQUEST['category_id']);
$warehouse_id = intval($_REQUEST['warehouse_id']);
$on_sale = $_REQUEST['on_sale'];
if($on_sale == -1) $on_sale='0';
$g = $_REQUEST['group'];
if($warehouse_id >0){
$info = getWarehouseInfo($warehouse_id);
$field = $info['dbfield'];
$warehouseName = $info['name'];
}else{
$field = '';
$vo = M('WarehouseName')->where('on_sale=1')->field('dbfield')->select();
foreach ($vo as $val){
$field .= (($field=='')?'':'+').$val['dbfield'];
}
}
if($g == 1){
$group = " group by B.category_id";
$field_g = ",B.category_id";
}
$A = $this->prefix .'warehouse A ';
$B = $this->prefix .'products B ';
$from 		= " FROM $A
						LEFT JOIN $B ON A.product_id=B.id
						";
$where 		= "";
if($category_id >0 &&$g != 1)$where 		.= (($where=='')?' WHERE ':' AND ')." B.category_id=$category_id";
if($on_sale != '')$where 		.= (($where=='')?' WHERE ':' AND ')." B.on_sale=$on_sale";
if($g == 1)$where 		.= (($where=='')?' WHERE ':' AND ')." B.category_id>0";
$Sql	= " SELECT
					SUM(($field)*B.stock_price) amount$field_g
					$from $where $group";
$vo = M()->query($Sql);
if($g == 1){
foreach ($vo as $val)
{
if($val['amount'] >0)
$ret[] = $val;
}
$h[] = array('display'=>'仓库金额','name'=>'amount','width'=>'100','align'=>'right');
$h[] = array('display'=>'商品分类','name'=>'category_id','width'=>'300','align'=>'left','func'=>'getPicklistValue');
$this->assign('header',$h);
$this->assign('list',$ret);
}else{
if($warehouseName !=''){
$h[] = array('display'=>$warehouseName,'name'=>'amount','width'=>'100','align'=>'right');
}else{
$h[] = array('display'=>'仓库金额','name'=>'amount','width'=>'100','align'=>'right');
}
$this->assign('header',$h);
$this->assign('list',$vo);
}
$this->display('data_table');
}
}
?>