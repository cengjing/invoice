<?php

class PublicAction extends Action 
{
private $versionType = '免费版';
public function _initialize() 
{
if(!file_exists(getcwd()."/Public/install.lock"))
{
redirect(__ROOT__.'/install.php');
return;
}
}
public function login() 
{
$USER_AUTH_KEY = C('USER_AUTH_KEY');
if (!session("?$USER_AUTH_KEY"))
{
$path = getcwd();
$this->assign('version',include $path.'/version.php');
$this->assign('site_company',getConfigValue('site_company'));
$this->assign('site_logo',__ROOT__.substr(getConfigValue('site_logo'),1));
$this->assign('versionType',$this->versionType);
$this->display ('login');
}else {
$this->redirect ( 'index/index');
}
}
public function androidLogin()
{
$this->display('_index');
}
public function logOut() 
{
$USER_AUTH_KEY = C('USER_AUTH_KEY');
if (session("?$USER_AUTH_KEY")) 
{
$uid = session($USER_AUTH_KEY);
$cacheName = "userModulePerm_$uid";
S($cacheName,NULL);
session($USER_AUTH_KEY,null);
session(null);
$this->assign ( "jumpUrl",__URL__ .'/login/');
$this->success ( '登出成功！');
}else {
$this->error ( '已经登出！');
}
}
public function doLogIn() 
{
if (empty ( $_POST ['username'] )) 
{
$this->error ( '帐号错误！');
}elseif (empty ( $_POST ['password'] )) {
$this->error ( '密码必须！');
}elseif (empty ( $_POST ['verify'] )) 	{
$this->error ( '验证码必须！');
}
$map = array ();
$map ['username'] = $_POST ['username'];
$map ["status"] = array ('gt',0 );
if ($_SESSION ['verify'] != md5($_POST['verify'])) 
{
$this->error ( '验证码错误！');
}
$User = M ( 'User');
$vo = $User->where('status=1')->field('id')->select();
if(count($vo)!=1)
$this->error ( '用户数超出上限，不能登陆。');
import ( 'ORG.Util.RBAC');
$authInfo = RBAC::authenticate ( $map );
if (false === $authInfo) 
{
$this->error ( '帐号不存在或已禁用！');
}
else 
{
if ($authInfo ['password'] != md5 ( $_POST ['password'] )) 
{
$this->error ( '密码错误！');
}
session(C('USER_AUTH_KEY'),$authInfo['id']);
session('username',$authInfo['username']);
session('name',$authInfo['name']);
session('superAdmin',$authInfo['superAdmin']);
if ($authInfo ['isAdmin'] == '1') 
{
session('administrator',true);
}
$time = time ();
$data = array ();
$data ['id'] = $authInfo ['id'];
$data ['last_login_time'] = $time;
$data ['login_count'] = array ('exp','login_count+1');
$User->save ( $data );
if(!session('?sales_assistant'))
{
}
RBAC::saveAccessList ();
$REQUEST_URI = session('REQUEST_URI');
if(!empty($REQUEST_URI)){
redirect($REQUEST_URI);
}else{
$this->redirect ('index/index');
}
}
}
public function verify() {
import ( 'ORG.Util.Image');
if (isset ( $_REQUEST ['adv'] )) {
Image::showAdvVerify ();
}else {
Image::buildImageVerify ($length=4,$mode=1,$type='png',$width=48,$height=25);
}
}
}?>