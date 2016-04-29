<?php

class ConfigAction extends GlobalAction 
{
public function index()
{
$this->setTitle('公司设置');
$serverInfo['服务器系统及PHP版本']	= PHP_OS.' / PHP v'.PHP_VERSION;
$serverInfo['服务器软件'] 			= $_SERVER['SERVER_SOFTWARE'];
$serverInfo['最大上传许可']     	= ( @ini_get('file_uploads') )?ini_get('upload_max_filesize') : '<font color="red">no</font>';
$mysqlinfo = M('')->query("SELECT VERSION() as version");
$serverInfo['MySQL版本']			= $mysqlinfo[0]['version'] ;
$t = M('')->query("SHOW TABLE STATUS LIKE '".C('DB_PREFIX')."%'");
foreach ($t as $k)
{
$dbsize += $k['Data_length'] +$k['Index_length'];
}
$serverInfo['数据库大小']			= byte_format( $dbsize );
$this->assign('serverInfo',$serverInfo);
$vo = M('Config')->select();
foreach ($vo as $val)
{
$this->assign($val['name'],$val['value']);
}
$param = array();
$param['name']		= 'assistant_module';
$param['module']	= 'Group';
$param['filter']	= 'func|getAssistantModule';
$param['value']		= $this->__get('assistant_module');
$this->assign('assistantModule',$param);
$this->display();
}
public function save()
{
$model = M('Config');
$_POST['company_name_repeat'] = isset($_REQUEST['company_name_repeat'])?1:0;
$_POST['company_credit'] = isset($_REQUEST['company_credit'])?1:0;
$_POST['user_credit'] = isset($_REQUEST['user_credit'])?1:0;
$_POST['allow_upload_file'] = isset($_REQUEST['allow_upload_file'])?1:0;
$val = $_POST['assistant_module'];
$sm = '';
foreach ($val as $v)
{
$sm .= (($sm == '')?'':',').$v;
}
$_POST['assistant_module'] = $sm;
foreach ($_POST as $k=>$v)
{
unset($map);
unset($data);
if($k != 'logo'){
$map['name'] = $k;
$data['value'] = $v;
$model->where($map)->save($data);
}
}
if(!empty($_FILES))
{
$path = './Public/uploads/logo/';
checkDir($path);
import("ORG.Net.UploadFile");
$upload = new UploadFile();
$upload->maxSize	=	'2000000';
$upload->allowExts	=	explode(',',strtolower('jpg,gif,png,jpeg'));
$upload->saveRule	=	'uniqid';
$upload->savePath	=	$path;
if($upload->upload()) {
$info = $upload->getUploadFileInfo();
if($info){
$file = $info[0]['savepath'].$info[0]['savename'];
$map['name'] = 'site_logo';
$data['value'] = $file;
$model->where($map)->save($data);
}
}
}
S('Config',NULL);
$this->success('修改成功');
}
public function clearCache()
{
import('ORG.Io.Dir');
Dir::del(getcwd()."/App/Runtime/Temp");
Dir::del(getcwd()."/App/Runtime");
Dir::del(getcwd()."/App/Runtime/Data/_fields/");
$this->success('成功清除服务器缓存。');
}
}
?>