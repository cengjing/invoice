<?php

class GridWidget extends Widget {
public function render($data)
{
import ( 'ORG.Util.String');
$data['rand'] = String::randNumber(10000000,99999999);
if(isset($data['height'])){
$data['bDivHeight'] = $data['height'] -27;
}
if (!isset($data['name']))$data['name'] = $data['rand'];
parse_str($_SERVER['QUERY_STRING'],$vars);
if (isset($data['module']))
{
$map['module'] = $data['module'];
$access = D('User')->getAccessFields(strtolower($data['module']));
if(!$access['default']['all'])
{
$map['field'] = array('IN',array_keys($access['default']['fields']));
}
$vo = M('Grid')->where($map)->order('seq ASC')->select();
$sort_field = '';
foreach ($vo as $v)
{
if(isset($vars['_sort_'.$v['name']]))
{
if($sort_field == '')
{
$sort_field = '_sort_'.$v['name'];
$sort = $vars['_sort_'.$v['name']];
}
unset($vars['_sort_'.$v['name']]);
}
}
$header = array();
foreach ($vo as $v)
{
$tmp = $vars;
if($v['sort'] == 1)
{
if('_sort_'.$v['name'] == $sort_field)
{
$tmp[$sort_field] = ( strtolower($sort) == 'asc')?'desc': 'asc';
}else{
$tmp['_sort_'.$v['name']] = 'desc';
}
if(!empty($tmp)) 
{
$v['sort_url'] = strtolower(MODULE_NAME).'?'.urldecode(http_build_query($tmp));
if('_sort_'.$v['name'] == $sort_field)
{
$v['sort_asc'] = $tmp['_sort_'.$v['name']] == 'asc'?true : false;
}
}
}
$header[] = $v;
}
$data['header'] = $header;
}
$content = $this->renderFile ( 'index',$data );
return $content;
}
}?>