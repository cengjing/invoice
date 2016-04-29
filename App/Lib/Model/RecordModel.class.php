<?php

class RecordModel extends Model 
{
public function insert($id,$content,$notify=false,$module)
{
$map['recordId'] 	= $id;
$map['module'] 		= strtolower($module);
$map['content'] 	= $content;
$map['cTime'] 		= time();
$map['uid'] 		= session(C ( 'USER_AUTH_KEY'));
$this->add($map);
if($notify)
{
$this->addNotify($id,$content,$module);
}
}
protected function addNotify($id,$content,$module)
{
return;
$map['module'] = $module;
$model = D($module);
$vo = M('WorkflowModule')->where($map)->find();
$moduleName = $vo['title'];
if( method_exists($model,'getInfo')) 
{
$module = strtolower($module);
$url = __APP__."/$module/show/id/$id";
$vo = $model->getInfo($id);
foreach ($vo['owner'] as $v){
$toUserIds[] = $v;
}
$arr = getConfigValue('assistant_module');
foreach ($arr as $val){
if(strtoupper($val) == strtoupper($module))
{
$users = session('sales_assistant');
foreach ($users['assistants'] as $v){
$toUserIds[] = $v;
}
break;
}
}
$tmp = "$moduleName<a href=\"$url\">$id</a>，";
}
if(count($toUserIds)>0)
{
$data['fromUserId'] 	= session(C('USER_AUTH_KEY'));
$data['content'] 		= $tmp.$content;
$data['cTime'] 			= time ();
$recordId = M('Msg')->add($data);
$map ['recordId'] 		= $recordId;
$map ['type'] 			= 'notify';
$model = M ('Members');
$toUserIds = array_unique($toUserIds);
foreach ( $toUserIds as $userId )
{
$map ['toUserId'] = $userId;
$model->add ( $map );
}
}
}
}?>