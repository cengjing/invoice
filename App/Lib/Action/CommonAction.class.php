<?php

class CommonAction extends GlobalAction
{
public function download()
{
import ( "ORG.Net.Http");
$model = D ( "Attach");
$id = intval($_REQUEST ['id']);
$vo = $model->where ( "id=$id")->find ();
$module = strtolower($vo['module']);
$record_id = $vo['recordId'];
$uid = $this->uid;
$perm = false;
switch ($module)
{
case "feedback":
$map['type']		= 'feed';
$map['recordId']	= $record_id;
$members =M('Members')->where($map)->select();
foreach ($members as $v)
{
if($uid == $v['toUserId'])
{
$perm = true;
break;
}
}
break;
case "news":
$perm = true;
break;
case "documents":
$perm = true;
break;
default:
$ret = D('Msg')->getPerm($uid,$record_id);
if ($ret)
{
$perm = true;
}
}
if($perm == false)
{
$this->error('您没有权限下载这个附件');
}
$filename = $vo ['savepath'] .$vo ['savename'];
if (is_file ( $filename ))
{
$model->setInc( 'downCount');
Http::download ( $filename,auto_charset ( $vo ['name'],'utf-8','gbk') );
}
}
public function upload()
{
$module = $_REQUEST['upload_module'];
$record_id = $_REQUEST['upload_record_id'];
$info = parent::_upload ($module,$record_id);
if(is_array($info))
{
$info = $info[0];
D('Record')->insert($record_id,'上传附件，'.$info['name'],false,$module);
$tmp = explode('.',$info['savepath']);
$attach_id = $info['attach_id'];
echo '<script language="javascript" type="text/javascript">window.top.window.stopFileUpload('.$attach_id .',"");</script>';
}else{
$attach_id = 0;
$error = (is_string($info))?$info:'上传文件失败';
echo '<script language="javascript" type="text/javascript">window.top.window.stopFileUpload('.$attach_id .',"'.$error .'");</script>';
}
}
public function deleteFile()
{
$id = intval($_REQUEST ['id']);
$map['id'] = $id;
$model = M('Attach');
$uid = $this->uid;
$vo = $model->where($map)->find();
$user_id 		= $vo['userId'];
$record_id 		= $vo['recordId'];
$module 		= $vo['module'];
$fileName 		= $vo['name'];
$path = $vo['savepath'].$vo['savename'];
if($user_id != $uid)
{
$this->ajaxReturn ( '','删除失败，你没有权限删除。',0 );
}
D('Record')->insert($record_id,'删除附件，'.$fileName,false,$module);
$model->where($map)->delete();
unlink($path);
$this->ajaxReturn ( '','',1 );
}
public function reloadFiles()
{
if(!$this->isAjax())return;
$map['module'] = $_REQUEST['upload_module'];
$map['recordId'] = $_REQUEST['upload_record_id'];
$vo = M('Attach')->where($map)->select();
$this->assign('files',$vo);
$this->assign('uid',$this->uid);
$this->display('files');
}
}
?>