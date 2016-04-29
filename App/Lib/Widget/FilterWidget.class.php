<?php

class FilterWidget extends Widget 
{
public function render($data)
{
if($data['allFields'] == true){
return $this->renderFile ( "allFields",$data);
}
$map['module'] = $data['module'];
if($data['static'])
{
$map['static'] 	= 1;
$data['static'] = 1;
}else{
$map['filter'] 	= 1;
$data['static'] = 0;
}
$field = $data['fields'];
if(count($field)>0) {
$map['field'] = array('in',$field);
}else{
$access = D('User')->getAccessFields(strtolower($data['module']));
if(!$access['default']['all'])
{
$map['field'] = array('IN',array_keys($access['default']['fields']));
}
}
$vo = M('Fields')->where($map)->order('pid ASC,seq ASC')->select();
foreach ($vo as &$v)
{
if($v['type'] == 5){
$items = explode("\r\n",$v['select_items']);
$ret = array();
foreach ($items as $item){
$tmp = explode(",",$item);
$ret[] = $tmp;
}
$v['items'] = $ret;
}elseif ($v['type'] == 4){
if(empty($v['select_items'])){
$v['items'] = M($v['from_module'])->select();
}else{
$arr = explode('|',$v['select_items']);
if($arr[0] == 'where'){
$v['items'] = M($v['from_module'])->where($arr[1])->select();
}elseif ($arr[0] == 'func'){
$v['items'] = D($v['from_module'])->$arr[1]();
}
}
}
}
$data ['field'] = $vo;
if(isset($_REQUEST['listRows']))
{
$data['listRows'] = $_REQUEST['listRows'];
}elseif (isset($data['listRows']))
{
}else{
$data['listRows'] = getConfigValue('list_rows');
}
$headerAccess = session('_header_access');
if(!empty($headerAccess[strtoupper(MODULE_NAME)]['EXPORT']))
{
$data['export'] = true;
}
if($data['type'] == 'ajax'){
$content = $this->renderFile ( "allFields",$data );
}elseif ($data['type'] == 'page') {
$content = $this->renderFile ( "page",$data );
}else{
$content = $this->renderFile ( "Filter",$data );
}
return $content;
}
}?>