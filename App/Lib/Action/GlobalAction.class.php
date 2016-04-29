<?php

class GlobalAction extends Action 
{
protected $prefix;
protected $uid;
public function _initialize() 
{
if(!file_exists(getcwd()."/Public/install.lock"))
{
redirect(__ROOT__.'/install.php');
return;
}
$this->prefix = C('DB_PREFIX');
$this->uid = session(C('USER_AUTH_KEY'));
$this->_safeInput();
$this->_access();
if(!$this->isAjax())
{
if(getConfigValue('online_status') == 2){
D('UserOnline')->refreshOnline();
}
$this->assign('site_company',getConfigValue('site_company'));
$this->assign('site_logo',__ROOT__.substr(getConfigValue('site_logo'),1));
import("ORG.Util.Date");
$date = new Date(time());
$this->assign('year',$date->year);
$this->assign('month',$date->month);
$this->assign('day',$date->day);
$this->assign('week',$date->cWeekday);
}
}
private function _access()
{
if(!session(C('USER_AUTH_KEY')))
{
if($this->isAjax())return;
session('REQUEST_URI',$_SERVER['REQUEST_URI']);
redirect( PHP_FILE .C('USER_AUTH_GATEWAY') );
}
import('ORG.Util.RBAC');
if(C('USER_AUTH_ON') &&!in_array(MODULE_NAME,explode(',',C('NOT_AUTH_MODULE'))))
{
if(!RBAC::AccessDecision ())
{
if(C('RBAC_ERROR_PAGE'))
{
redirect( C('RBAC_ERROR_PAGE') );
}
else
{
if(C('GUEST_AUTH_ON')){
$this->assign ( 'jumpUrl',PHP_FILE .C('USER_AUTH_GATEWAY') );
}
if ($this->isAjax()){
if($_POST['format'] == 'page'){
$this->display('public:unvalid-access');
}else{
$this->error ( L ( '_VALID_ACCESS_') );
}
exit;
}else{
$this->error ( L ( '_VALID_ACCESS_') );
}
}
}
}
if(!$this->isAjax()){
if(!session('?_header_access') ||C('USER_AUTH_TYPE')==2 )
{
$access = RBAC::getAccessList ($this->uid);
session('_header_access',$access['APP']);
}
$this->createMenu(session('_header_access'),session('administrator'));
}
}
public function _index() {
$map = $this->_search ();
if (method_exists ( $this,'_filter')) {
$this->_filter ( $map );
}
$name = $this->getActionName ();
$model = D($name);
if (!empty ( $model )) {
$this->_list ( $model,$map );
}
$this->display ();
}
protected function _search($name = '') {
if (empty ( $name )) {
$name = $this->getActionName ();
}
$name = $this->getActionName ();
$model = D ( $name );
$map = array ();
$fields = $model->getDbFields ();
foreach ( $fields as $val ) {
if (isset ( $_REQUEST [$val] ) &&$_REQUEST [$val] != '') {
$map [$val] = $_REQUEST [$val];
}
}
return $map;
}
protected function _pageList($countSQL,$pageSQL,$arr_sum=array(),$listRows=20) {
$model = M();
$vo = $model->query ( $countSQL );
$count = $vo [0] ['COUNT(*)'];
if ($count >0) {
if(sizeof($arr_sum)){
foreach ($arr_sum as $v){$sum[$v] = formatCurrency($vo[0][$v]);}
$this->assign ( 'sum',$sum );
}
import ( "ORG.Util.ZQPage");
$listRows = $_REQUEST['listRows'];
if(empty($listRows)){
$listRows = getConfigValue('list_rows');
}
if(empty($listRows)) $listRows = 20;
$maxListRows = getConfigValue('max_list_rows');
if($listRows >$maxListRows)$listRows = $maxListRows;
$p = new ZQPage($count,$listRows ,5,0,2);
$vo = $model->query ( $pageSQL ." LIMIT $p->firstRow,$p->listRows ");
$page = $p->show ();
$this->assign ( 'list',$vo );
$this->assign ( 'page',$page );
}
}
protected function _list($model,$map,$link,$sortBy = '',$asc = false,$group = '') {
if (isset ( $_REQUEST ['_order'] )) {
$order = $_REQUEST ['_order'];
}else {
$order = !empty ( $sortBy ) ?$sortBy : $model->getPk ();
}
if (isset ( $_REQUEST ['_sort'] )) {
$sort = $_REQUEST ['_sort'];
}else {
$sort = $asc ?'asc': 'desc';
}
$count = $model->where ( $map )->count ( 'id');
if ($count >0) {
import ( "ORG.Util.Page");
if (!empty ( $_REQUEST ['listRows'] )) {
$listRows = $_REQUEST ['listRows'];
}else {
$listRows = '20';
}
$p = new Page ( $count,$listRows );
$A = $model->getTableName ();
$tmp = array ();
foreach ( $map as $k =>$v ) {
$tmp ["$A.$k"] = $v;
}
unset ( $map );
$map = $tmp;
$model->where ( $map )->order ( "$A.$order $sort")->limit ( $p->firstRow .','.$p->listRows );
$field = "$A.*";
if (isset ( $model->_link ))
$link = $model->_link;
if (is_array ( $link )) {
foreach ( $link as $k =>$v ) {
$link_linkId = $v ['linkId'];
$link_id = $v ['on'];
$model->join ( "$this->prefix$k ON $A.$link_linkId=$this->prefix$k.$link_id");
if (isset ( $v ['field'] )) {
$fields = explode ( ',',$v ['field'] );
$asName = explode ( ',',$v ['as'] );
foreach ( $fields as $subk =>$subv ) {
if ($subv != '')
$field .= ",$this->prefix$k.$subv AS $asName[$subk]";
}
}
$fields = explode ( ',',$v ['groupfield'] );
$asName = explode ( ',',$v ['group_as'] );
foreach ( $fields as $subk =>$subv ) {
if ($subv != '')
$field .= ",GROUP_CONCAT( $this->prefix$k.$subv SEPARATOR ',' )AS $asName[$subk]";
}
}
}
$voList = $model->group ( $group )->field ( $field )->select();
$page = $p->show ();
$sortImg = $sort;
$sortAlt = $sort == 'desc'?'升序排列': '倒序排列';
$sort = $sort == 'desc'?1 : 0;
$this->assign ( 'list',$voList );
$this->assign ( 'sort',$sort );
$this->assign ( 'order',$order );
$this->assign ( 'sortImg',$sortImg );
$this->assign ( 'sortType',$sortAlt );
$this->assign ( "page",$page );
}
return;
}
public function _insert() 
{
if (method_exists ( $this,'_operation')) {
$this->_operation ();
}
$name = $this->getActionName ();
$model = D ( $name );
if (false === $model->create ()) 
{
$this->error ( $model->getError () );
}
$id = $model->add ();
if ($id !== false) {
if (!empty ( $_FILES )) {
$this->_upload ( $model->getModelName (),$id );
}
$this->success ( '新增成功!');
}else {
$this->error ( '新增失败!');
}
}
public function _edit() 
{
$name = $this->getActionName ();
$model = D ( $name );
$id = $_REQUEST [$model->getPk ()];
$vo = $model->getById ( $id );
$this->assign ( 'vo',$vo );
$this->display ();
}
public function _show()
{
$name = $this->getActionName ();
$model = M($name);
$id = $_REQUEST [$model->getPk ()];
$vo = $model->getById ( $id );
$this->assign ( 'vo',$vo );
if (method_exists ( $this,'_showTitle')) {
$this->_showTitle ( $vo );
}
$this->display ();
}
public function _update() 
{
$name = $this->getActionName ();
$model = D($name);
if (false === $model->create ()) {
$this->error ( $model->getError () );
}
$result = $model->save ();
if (false !== $result) {
$this->success ( '编辑成功!');
}else {
$this->error ( '编辑失败!');
}
}
public function _add() {
$this->display ();
}
public function _delete() {
$name = $this->getActionName ();
$model = M ( $name );
if (!empty ( $model )) {
$pk = $model->getPk ();
$id = $_REQUEST [$pk];
if (isset ( $id )) {
$condition = array ($pk =>array ('in',explode ( ',',$id ) ) );
$list = $model->where ( $condition )->setField ( 'status',6 );
if ($list !== false) {
$this->success ( '删除成功！');
}else {
$this->error ( '删除失败！');
}
}else {
$this->error ( '非法操作');
}
}
}
protected function _upload($module,$recordId,$savePath='./Public/uploads/a/') 
{
import ( "ORG.Net.UploadFile");
$savePath .= date('Y') .'/';
$savePath .= date('m') .'/';
$savePath .= date('d') .'/';
checkDir($savePath);
$upload = new UploadFile ( );
$max_upload_size = floatval(getConfigValue('max_upload_file'));
if($max_upload_size == 0 )
{
$max_upload_size = ( @ini_get('file_uploads') )?ini_get('upload_max_filesize') : 0;
}
$upload->maxSize =  $max_upload_size * 1024 * 1024;
$upload->saveRule = 'uniqid';
$upload->savePath = $savePath;
if (!$upload->upload ()) {
return $upload->getErrorMsg ();
}else {
$info = $upload->getUploadFileInfo ();
}
$Attach = M ('Attach');
if ($info) 
{
foreach ( $info as &$v ) 
{
$data ['savepath'] = $v ['savepath'];
$data ['userId'] = $this->uid;
$data ['savename'] = $v ['savename'];
$data ['name'] = $v ['name'];
$data ['size'] = $v ['size'];
$data ['extension'] = $v ['extension'];
$data ['module'] = $module;
$data ['recordId'] = $recordId;
$data ['uploadTime'] = time ();
$v['attach_id'] = $Attach->add ( $data );
}
return $info;
}
return false;
}
public function deleteAttachment()
{
$id = $_REQUEST ['aId'];
$result =M('Attach')->where("id=$id")->delete();
echo $result ?1 : 0;
}
private function _safeInput()
{
import('ORG.Util.Input');
if(sizeof($_POST)>0 ||sizeof($_GET)>0)
{
if(function_exists('stripslashes_deep')) 
{
Input::noGPC();
}
}
}
protected function getFilter($module,$table='',$all = false,$filter = true)
{
$filter_type = $filter?'filter':'static';
$map['module'] = $module;
$map[$filter_type] = 1;
$access = D('User')->getAccessFields(strtolower($module));
if(!$access['default']['all'])
{
$map['field'] = array('IN',array_keys($access['default']['fields']));
}
$vo = M('Fields')->where($map)->order('seq asc')->select();
if ($all)
{
$users['all'] = true;
}else 
{
$users = D('GroupUser')->getUsers($module);
}
unset($map);
$selectUser = false;
$uids = '';
$only_id = false;
import('ORG.Util.Input');
foreach ($vo as $v)
{
if($only_id) break;
$f = $v['field'];
switch ( intval($v['type']) )
{
case 1:
if( $_REQUEST[$f] == '')break;
if($v['select_items'] == 1){
$l = ' LIKE \'%';
$r = '%\'';
}else{
$l = '=\'';
$r = '\'';
}
$field = Input::getVar($_REQUEST[$f]);
$preTableName = ($v['from_module'] != '')?$v['from_module']:$table;
$map[] = $preTableName.$f.$l.$field.$r;
$arr[$f] = $field;
break;
case 2:
$preTableName = ($v['from_module'] != '')?$v['from_module']:$table;
if($_REQUEST[$f.'3'] != '') {
$map[] = $preTableName.$f.'='.floatval($_REQUEST[$f.'3']);
}elseif($_REQUEST[$f.'1'] != ''&&$_REQUEST[$f.'2'] != '') {
$map[] = ' ('.$preTableName.$f.'>='.floatval($_REQUEST[$f.'1']).' AND '.$preTableName.$f.'<='.floatval($_REQUEST[$f.'2']).') ';
}elseif($_REQUEST[$f.'1'] != ''&&$_REQUEST[$f.'2'] == '') {
$map[] = $preTableName.$f.'>='.floatval($_REQUEST[$f.'1']);
}elseif($_REQUEST[$f.'1'] == ''&&$_REQUEST[$f.'2'] != '') {
$map[] = $preTableName.$f.'<='.floatval($_REQUEST[$f.'2']);
}
$arr[$f.'1'] = $_REQUEST[$f.'1'];
$arr[$f.'2'] = $_REQUEST[$f.'2'];
$arr[$f.'3'] = $_REQUEST[$f.'3'];
break;
case 3:
$preTableName = ($v['from_module'] != '')?$v['from_module']:$table;
if(isset($_REQUEST[$f.'_null']))
{
$map[] = " ($preTableName".$f."='' OR $preTableName".$f." IS NULL) ";
}else{
if(!empty($_REQUEST[$f.'1'])){
$date = explode('-',$_REQUEST[$f.'1']);
$time1 = mktime(0,0,0,$date[1],$date[2],$date[0]);
$arr[$f.'1'] = $_REQUEST[$f.'1'];
}
if(!empty($_REQUEST[$f.'2'])){
$date = explode('-',$_REQUEST[$f.'2']);
$time2 = mktime(24,59,59,$date[1],$date[2],$date[0]);
$arr[$f.'2'] = $_REQUEST[$f.'2'];
}
if(!empty($_REQUEST[$f.'1']) &&!empty($_REQUEST[$f.'2'])){
$map[] = " ($preTableName".$f.">=$time1 AND $preTableName".$f."<=$time2) ";
}elseif (!empty($_REQUEST[$f.'1']) &&empty($_REQUEST[$f.'2'])){
$map[] = " $preTableName".$f.">=$time1 ";
}elseif (empty($_REQUEST[$f.'1']) &&!empty($_REQUEST[$f.'2'])){
$map[] = " $preTableName".$f."<=$time2 ";
}
}
break;
case 4:
if( $_REQUEST[$f] == '')break;
$map[] = $table.$f.'='.intval($_REQUEST[$f]);
$arr[$f] = $_REQUEST[$v['field']];
break;
case 5:
if( $_REQUEST[$f] == '')break;
$map[] = $table.$f.'='.intval($_REQUEST[$f]);
$arr[$f] = $_REQUEST[$v['field']];
break;
case 6:
if( $_REQUEST['others'] == '')break;
if($_REQUEST['all'] == true &&$users['all']  == true)break;
$others = $_REQUEST['others'];
$tmp = explode(',',$others);
foreach ($users['users'] as $u){
foreach ($tmp as $id){
if($u['id'] == $id){
$uids .= ($uids == ''?'':',').'\''.$u['id'].'\'';
break;
}
}
}
$preTableName = ($v['from_module'] != '') ?$v['from_module'] : $table;
$map[] = $preTableName.$f." IN ($uids)";
$selectUser = true;
break;
case 7:
if( $_REQUEST[$f] == '')break;
$field = Input::getVar($_REQUEST[$f]);
unset($map);
$map[] = $table.$f.'=\''.$field.'\'';
$arr[$f] = $_REQUEST[$f];
$only_id = true;
break;
case 8:
if( $_REQUEST[$f] == '')break;
$preTableName = ($v['select_items'] != '') ?$v['select_items'] : $table;
$field = Input::getVar($_REQUEST[$f]);
$map[] = $preTableName.$f.'=\''.$field.'\'';
$arr[$f] = $_REQUEST[$f];
break;
case 9:
if( $_REQUEST[$f] == '')break;
if($v['select_items'] == 1){
$l = ' LIKE \'%';
$r = '%\'';
}else{
$l = '=\'';
$r = '\'';
}
$field = Input::getVar($_REQUEST[$f]);
$preTableName = ($v['select_items'] != '') ?$v['select_items'] : $table;
$map[] = $preTableName.$f.$l.$field.$r;
$arr[$f] = $_REQUEST[$f];
break;
case 10:
if( $_REQUEST[$f] == '')break;
if( $_REQUEST[$f] == -1){
$_REQUEST[$f] = 0;
}
$map[] = $table.$f.'='.intval($_REQUEST[$f]);
break;
case 11:
if( $_REQUEST[$f] == '')break;
$field = Input::getVar($_REQUEST[$f]);
$map[] = $table.$f.'=\''.$field .'\'';
$arr[$f] = $_REQUEST[$f];
break;
case 12:
if( $_REQUEST[$f] == '')break;
$s = '';
foreach ($_REQUEST[$f] as $v){
$s .= ($s==''?'':',')."'$v'";
}
$map[] = $table.$f.' IN ('.$s.')';
break;
}
}
$ret = '';
$order = '';
foreach ($vo as $v)
{
if($v['type'] == 2) 
{
$ret .= ',SUM('.$table.$v['field'].') sum_'.$v['field'];
$arr['sum_'.$v['field']] = 1;
$sum[] = 'sum_'.$v['field'];
}elseif ($v['type'] == 6 &&$selectUser == false &&$users['all']  == false){
foreach ($users['users'] as $u){
$uids .= ($uids == ''?'':',').'\''.$u['id'].'\'';
}
$prefix = ($v['from_module'] != '') ?$v['from_module'] : $table;
$map[] = $prefix.$v['field']." IN ($uids)";
$selectUser = true;
}
if(isset($_REQUEST['_sort_'.$v['field']]) &&$order == '')
{
$prefix = ($v['from_module'] != '') ?$v['from_module'] : $table;
$order = $prefix.$v['field'].' '.$_REQUEST['_sort_'.$v['field']];
}
}
$result['sum'] = $ret;
$result['arr_sum'] = $sum;
$this->assign('arr',$arr);
$ret = '';
if(sizeof($map)>0){
$this->assign('map',$map);
foreach ($map as $v){
$ret .= ($ret == ''?'':' AND ').$v;
}
}
$result['where'] = $ret;
$result['order'] = $order;
$result['perm'] = $users;
return $result;
}
protected function setTitle($title)
{
$this->assign('title',$title);
}
protected function validate($module='',$action='add')
{
if(empty($module))
{
$module = $this->getActionName();
}
$map['module']	= $module;
$map[$action] 	= 1;
$map['check']	= 1;
$vo = M('Fields')->where($map)->order('seq ASC')->select();
foreach ($vo as $v)
{
if( $v['type'] == 11 &&intval($_POST[$v['field']]) == 0 ){
$error = '请填写'.$v['title'];
}elseif(''== trim($_POST[$v['field']])){
$error = '请填写'.$v['title'];
}
if (!empty($error))
$this->error($error);
}
}
private function createMenu($access,$admin = false)
{
$this->assign('access',$access);
$menu = '';
if ($admin ||isset($access['ACCOUNTS']) ||isset($access['CONTACTS']) ||isset($access['ACTIVITY']) ||isset($access['ACTIVITY']) ||isset($access['MAIL']) ||isset($access['CREDIT']) )
{
$menu .= '<li class="subnav"><div class="dir">客户</div>';
$menu .= '<ul>';
if (isset($access['ACCOUNTS']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/accounts">客户信息</a>';
if (isset($access['ACCOUNTS']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/accounts/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['CONTACTS']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/contacts">联系人</a>';
if (isset($access['CONTACTS']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/contacts/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['ACTIVITY']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/activity">联系记录</a>';
if (isset($access['ACTIVITY']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/activity/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['MAIL']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/mail">群发邮件</a>';
if (isset($access['MAIL']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/mail/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['CREDIT']) ||$admin)
{
$menu .= '<li class="divider"><a href="__APP__/credit">信用管理</a></li>';
}
$menu .= '</ul>';
$menu .= '</li>';
}
if ( $admin ||isset($access['QUOTES']) ||isset($access['SALESORDER']) ||isset($access['DELIVERY']) ||isset($access['INVOICE']) ||isset($access['RETURNS']) ||isset($access['PRODUCTS']) ||isset($access['WAREHOUSE']) )
{
$menu .= '<li class="subnav"><div class="dir">销售</div>';
$menu .= '<ul>';
if (isset($access['QUOTES']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/quotes">报价单</a>';
if (isset($access['QUOTES']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/quotes/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['SALESORDER']) ||$admin)
{
$menu .= '<li class="divider clearfix">';
$menu .= '<a class="left" href="__APP__/salesorder">销售订单</a>';
if (isset($access['SALESORDER']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/salesorder/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['DELIVERY']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/delivery">销售发货</a>';
if (isset($access['DELIVERY']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/delivery/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['INVOICE']) ||$admin)
{
$menu .= '<li><div class="dir">销售发票</div>';
$menu .= '<ul>';
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/invoice">蓝字销售发票</a>';
if (isset($access['INVOICE']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/invoice/add">新建</a>';
}
$menu .= '</li>';
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/redinvoice">红字销售发票</a>';
if (isset($access['INVOICE']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/redinvoice/add">新建</a>';
}
$menu .= '</li>';
$menu .= '</ul>';
$menu .= '</li>';
}
if (isset($access['RETURNS']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/returns">销售退货</a>';
if (isset($access['RETURNS']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/returns/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['PRODUCTS']) ||$admin)
{
$menu .= '<li class="divider"><a href="__APP__/products">商品目录</a></li>';
}
if (isset($access['WAREHOUSE']) ||$admin)
{
$menu .= '<li><a href="__APP__/warehouse">库存查询</a></li>';
}
$menu .= '</ul>';
$menu .= '</li>';
}
if ($admin ||isset($access['WAREHOUSE']) ||isset($access['STOCKOUT']) ||isset($access['STOCKIN']) ||isset($access['ALLOT']) ||isset($access['STOCKTAKING']) )
{
$menu .= '<li class="subnav"><div class="dir">库存</div>';
$menu .= '<ul>';
if (isset($access['WAREHOUSE']) ||$admin)
{
$menu .= '<li><a href="__APP__/warehouse">库存查询</a></li>';
}
if (isset($access['STOCKOUT']) ||$admin)
{
$menu .= '<li><a href="__APP__/stockout">发货</a></li>';
}
if (isset($access['STOCKIN']) ||$admin)
{
$menu .= '<li><a href="__APP__/stockin">入库</a></li>';
}
if (isset($access['ALLOT']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/allot">调拨</a>';
if (isset($access['ALLOT']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/allot/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['STOCKTAKING']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/stocktaking">盘点</a>';
if (isset($access['STOCKTAKING']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/stocktaking/add">新建</a>';
}
$menu .= '</li>';
}
$menu .= '</ul>';
$menu .= '</li>';
}
if($admin ||isset($access['VENDER'])||isset($access['PRICE']) ||isset($access['DISCOUNT']) ||isset($access['PURCHASE']) ||isset($access['ARRIVAL']) ||isset($access['PURCHASERETURN']) ||isset($access['PURCHASEINVOICE']) )
{
$menu .= '<li class="subnav"><div class="dir">采购</div>';
$menu .= '<ul>';
if (isset($access['PRODUCTS']) ||$admin)
{
$menu .= '<li><a href="__APP__/products">商品目录</a></li>';
}
if (isset($access['PRICE']) ||$admin)
{
$menu .= '<li><a href="__APP__/price">销售价格管理</a></li>';
}
if (isset($access['DISCOUNT']) ||$admin)
{
$menu .= '<li><a href="__APP__/discount">折扣管理</a></li>';
}
if (isset($access['VENDER']) ||$admin)
{
$menu .= '<li class="divider"><a href="__APP__/vender">供货商</a></li>';
}
if (isset($access['PURCHASE']) ||$admin)
{
$menu .= '<li class="divider clearfix">';
$menu .= '<a class="left" href="__APP__/purchase">采购订单</a>';
if (isset($access['PURCHASE']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/purchase/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['ARRIVAL']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/arrival">采购到货</a>';
if (isset($access['ARRIVAL']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/arrival/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['PURCHASERETURN']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/purchasereturn">采购退货</a>';
if (isset($access['PURCHASERETURN']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/purchasereturn/add">新建</a>';
}
$menu .= '</li>';
}
if (isset($access['PURCHASEINVOICE']) ||$admin)
{
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/purchaseinvoice">采购发票</a>';
if (isset($access['PURCHASEINVOICE']['ADD']) ||$admin)
{
$menu .= '<a class="right" href="__APP__/purchaseinvoice/add">新建</a>';
}
$menu .= '</li>';
}
$menu .= '</ul>';
$menu .= '</li>';
}
if ($admin ||isset($access['INVOICEMANAGE']) ||isset($access['ACCOUNTRECEIVABLE']) ||isset($access['ACCOUNTPAYABLE']) )
{
$menu .= '<li class="subnav"><div class="dir">财务</div>';
$menu .= '<ul>';
if (isset($access['INVOICEMANAGE']) ||$admin)
{
$menu .= '<li><a href="__APP__/invoicemanage">发票管理</a></li>';
}
if (isset($access['ACCOUNTRECEIVABLE']) ||$admin)
{
$menu .= '<li class="divider"><div class="dir">应收款管理</div>';
$menu .= '<ul>';
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/accountreceivable">应收款</a>';
$menu .= '<a class="right" href="__APP__/accountreceivable/add">新建</a>';
$menu .= '</li>';
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/receipt">收款单</a>';
$menu .= '<a class="right" href="__APP__/receipt/add">新建</a>';
$menu .= '</li>';
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/reconciliation">应收核销</a>';
$menu .= '<a class="right" href="__APP__/reconciliation/add">新建</a>';
$menu .= '</li>';
$menu .= '</ul>';
$menu .= '</li>';
}
if (isset($access['ACCOUNTPAYABLE']) ||$admin)
{
$menu .= '<li class="divider"><div class="dir">应付款管理</div>';
$menu .= '<ul>';
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/accountpayable">应付款</a>';
$menu .= '<a class="right" href="__APP__/accountpayable/add">新建</a>';
$menu .= '</li>';
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/payment">付款单</a>';
$menu .= '<a class="right" href="__APP__/payment/add">新建</a>';
$menu .= '</li>';
$menu .= '<li class="clearfix">';
$menu .= '<a class="left" href="__APP__/verification">应付核销</a>';
$menu .= '<a class="right" href="__APP__/verification/add">新建</a>';
$menu .= '</li>';
$menu .= '</ul>';
$menu .= '</li>';
}
$menu .= '</ul>';
$menu .= '</li>';
}
if ( isset($access['STATIC']) ||isset($access['DETAILS']) ||$admin )
{
$menu .= '<li class="subnav"><div class="dir">报表</div>';
$menu .= '<ul>';
if (isset($access['STATIC']) ||$admin)
{
$menu .= '<li><div class="dir">统计表</div>';
$menu .= '<ul>';
if (isset($access['STATIC']['SOD']) ||$admin)
{
$menu .= '<li><a href="__APP__/static/sod">销售订单统计</a></li>';
}
if (isset($access['STATIC']['POD']) ||$admin)
{
$menu .= '<li><a href="__APP__/static/pod">采购订单统计</a></li>';
}
if (isset($access['STATIC']['POD']) ||$admin)
{
$menu .= '<li><a href="__APP__/static/wh">库存统计</a></li>';
}
$menu .= '</ul></li>';
}
if (isset($access['DETAILS']) ||$admin)
{
$menu .= '<li><div class="dir">明细表</div>';
$menu .= '<ul>';
if (isset($access['DETAILS']['SOD']) ||$admin)
{
$menu .= '<li><a href="__APP__/details/sod">销售明细统计</a></li>';
}
if (isset($access['DETAILS']['POD']) ||$admin)
{
$menu .= '<li><a href="__APP__/details/pod">采购明细统计</a></li>';
}
$menu .= '</ul></li>';
}
$menu .= '</ul>';
$menu .= '</li>';
}
if ($admin)
{
$menu .= '<li><a href="__APP__/setting">设置</a></li>';
}
$this->assign('menu',$menu);
}
}?>