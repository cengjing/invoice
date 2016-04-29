<?php

class FormWidget extends Widget 
{
public function render($data)
{
$map['module'] = $data['module'];
$type = strtolower($data['type']);
switch ($type) {
case 'add':
$map['add'] = 1;
break;
case 'edit':
$map['edit'] = 1;
break;
case 'superedit':
$map['super_edit'] = 1;
$data['superEdit'] = true;
$superEdit = true;
break;
case 'show':
$map['show'] = 1;
break;
case 'filter':
$map['filter'] = 1;
break;
default:
$map['print'] = 1;
break;
}
if ($type == 'superedit')$type = 'edit';
$page = $type;
if ($type == 'filter')$page = 'add';
$access = D('User')->getAccessFields(strtolower($data['module']));
if(!$access['default']['all'])
{
$where['field'] = array('IN',array_keys($access['default']['fields']));
$where['level'] = 1;
$where['_logic'] = 'or';
$map['_complex'] = $where;
}
$vo = M('Fields')->where($map)->order('level ASC,seq ASC')->select();
foreach ($vo as $v)
{
if($v['level'] == 1){
$fields[$v['id']]['title'] = $v['title'];
}else{
if($v['type'] == 2)
{
$v['readonly'] = ( $superEdit != true &&$v['readonly'] == 1 )?1:0;
}
if($v['type'] == 4 ||$v['type'] == 12)
{
$param = array();
$param['name']		= $v['field'];
$param['module']	= $v['from_module'];
$param['filter']	= $v['select_items'];
$param['value']		= $data['data'][$v['field']];
$v['_param']		= $param;
}
$fields[$v['pid']]['data'][] = $v;
}
}
foreach ($fields as &$value)
{
if(count($value['data']) == 1)
{
$value['data'][] = array('type'=>101);
}
}
$data ['fields'] = $fields;
$map = array();
$map['module'] = $data['module'];
$vo = M('Modules')->where($map)->find();
if($vo)
{
$map = array();
$map['module_id']	= $vo['id'];
$map['status']		= 1;
if($type == 'print')
{
$map['print'] 	= 1;
}
if(!$access['costom']['all'])
{
$map['dbfield'] = array('IN',array_keys($access['costom']['fields']));
}
$list = M('Customfields')->where($map)->order('seq ASC')->select();
if($list)
{
if(count($list) == 1) $list[] = null;
$data['def'] = $list;
}
}
$content = $this->renderFile($page,$data);
return $content;
}
}?>