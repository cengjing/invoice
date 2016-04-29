<?php

class SelectUserWidget extends Widget 
{
public function render($data) 
{
import('ORG.Util.String');
$data['rand'] = String::randNumber(10000000,99999999);
$type = isset($data['type'])?$data['type']:'page';
$result = D('GroupUser')->getUsers($data ['module']);
$users = $result['users'];
$data ['users'] = $users;
$dpts = D('Department')->getList('',false);
foreach ($users as $v)
{
$user_dpt[] = $v['department_id'];
}
foreach ($dpts as $dpt) 
{
if (in_array ($dpt['id'],$user_dpt))
{
$tmp[] = $dpt;
$pid = $dpt['pid'];
while ($pid >0)
{
foreach ($dpts as $d)
{
if($d['id'] == $pid)
{
$tmp[] = $d;
$pid = $d['pid'];
break;
}
}
}
}
}
$dpt_result = array();
foreach ($dpts as $dpt) 
{
foreach ($tmp as $t)
{
if($dpt['id'] == $t['id'])
{
$dpt_result[] = $dpt;
break;
}
}
}
$data ['dpts'] = $dpt_result;
if($type == 'page'){
$content = $this->renderFile("SelectUser",$data);
}else {
$content = $this->renderFile("SelectUserModal",$data);
}
return $content;
}
}?>