<?php

class UserModel extends BaseModel 
{
protected $_validate = array (
array ('username','require','请填写用户名！'),
array ('username','','用户名必须唯一存在！',0,'unique',Model::MODEL_BOTH ),
array ('password','require','密码必须！'),
array ('name','require','姓名必须！'),
array ('department_id','require','请选择部门！') 
);
protected $_status = array (
'1'=>'正常',
'2'=>'离职',
'3'=>'删除',
);
protected $_sex = array (
'1'=>'男',
'2'=>'女',
);
protected $_position = array (
'1'=>'主管',
'2'=>'副主管',
'3'=>'普通',
);
protected $_auto = array (
array ('password','md5',Model::MODEL_INSERT,'function'),
array ('cTime','time',Model::MODEL_INSERT,'function'),
array ('name','trim',Model::MODEL_BOTH,'function')
);
public function getSexStatus()
{
foreach ($this->_sex as $k=>$v)
{
$ret[] = array(
'id'=>$k,
'title'=>$v
);
}
return $ret;
}
public function getPosition()
{
foreach ($this->_position as $k=>$v)
{
$ret[] = array(
'id'=>$k,
'title'=>$v
);
}
return $ret;
}
function getInfo($id)
{
$vo = $this->where("id=$id")->find();
if($vo == null)return false;
$ret['base'] = $vo;
$vo = M('Department')->where('id='.$vo['department_id'])->find();
$ret['base']['department'] = $vo['title'];
$ret['department'] = $vo;
return $ret;
}
public function getUsersList($data,$type = 'person',$showAll = 0) 
{
$uid = session(C('USER_AUTH_KEY'));
$fields = 'id,username,department_id,status,name';
if ($showAll == 0)$map ['status'] = 1;
$type = strtolower($type);
switch ($type) {
case 'all':
$vo = $this->where($map)->order('position ASC')->field($fields)->select();
break;
case 'department':
$dpts = explode( ',',$data['department'] );
$model = D('Department');
foreach ($dpts as $v){
if($v!=''){
foreach ($model->getAllChildList($v) as $val){
array_push($dpts,$val);
}
}
}
$dpts_str = array2text($dpts);
$teams = explode( ',',$data['others'] );
$teams[] = $uid;
$teams_str = array2text($teams);
$where = " (department_id IN ($dpts_str) OR id IN ($teams_str)) ";
if($showAll == 0)$where .= ' AND status=1';
$vo = $this->where($where)->order('position ASC')->field($fields)->select();
break;
case 'others':
$teams = explode( ',',$data['others'] );
$teams[] = $uid;
$teams_str = array2text($teams);
$where = " id IN ($teams_str) ";
if($showAll == 0)$where .= ' AND status=1';
$vo = $this->where($where)->order('position ASC')->field($fields)->select();
break;
default :
$map ['id'] = $uid;
$vo = $this->where($map)->order('position ASC')->field($fields)->select();
}
return $vo;
}
public function doChangeFace($uid)
{
if(empty($_FILES)) return false;
$path = './Public/uploads/user/'.$uid.'/images/';
checkDir($path);
import("ORG.Net.UploadFile");
$upload = new UploadFile();
$upload->maxSize	=	'2000000';
$upload->allowExts	=	explode(',',strtolower('jpg,gif,jpeg'));
$upload->saveRule	=	'uniqid';
$upload->savePath	=	$path;
if(!$upload->upload()) {
return false;
}else{
$info = $upload->getUploadFileInfo();
}
if($info){
$file = $info[0]['savepath'].$info[0]['savename'];
$this->resizeImage($file,$uid);
$model = M('Attach');
$data['module'] = 'User';
$data['userId'] = $uid;
$vo = $model->where($data)->find();
$data['uploadTime'] = time();
$data['savepath'] = $info[0]['savepath'].$info[0]['savename'];
if(null != $vo)
{
$model->where("id=".$vo['id'])->save($data);
}else {
$model->add($data);
}
}
return true;
}
private function resizeImage($file,$uid)
{
import("ORG.Util.Image");
$img = new Image();
$img_info = $img->getImageInfo($file);
$s = 150;
if($img_info['width']>=$img_info['height']){
$w = $s;
$h = ($img_info['height']/$img_info['width'])*$w;
}else {
$h = $s;
$w = ($img_info['width']/$img_info['height'])*$h;
}
$face_s = './Public/uploads/user/'.$uid.'/face_s.jpg';
$img->thumb($file,$face_s,'jpg',$w,$h,false);
$s = 220;
if($img_info['width']>=$img_info['height']){
$w = $s;
$h = ($img_info['height']/$img_info['width'])*$w;
}else {
$h = $s;
$w = ($img_info['width']/$img_info['height'])*$h;
}
$face_l = './Public/uploads/user/'.$uid.'/face_l.jpg';
$img->thumb($file,$face_l,'jpg',$w,$h,false);
}
public function getMultiSalesWarehouse($uid=null)
{
$prefix = C('DB_PREFIX');
if($uid)
{
$A = $prefix.'warehouse_name A';
$B = $prefix.'user_sales_warehouse B';
$Sql = "SELECT A.*,A.name title,B.uid
					FROM $A LEFT JOIN $B ON (A.id=B.warehouse_id AND B.uid=$uid)
					WHERE A.status=1 ORDER BY A.seq ASC";
}
else
{
$A = $prefix.'warehouse_name A';
$Sql = "SELECT A.*,A.name title
					FROM $A WHERE A.status=1 ORDER BY A.seq ASC";
}
$vo = $this->query($Sql);
foreach ($vo as &$v)
{
if (isset($v['uid']) &&$v['uid'] == $uid)
{
$v['_checked'] = true;
}
}
return $vo;
}
public function getMultiStock($uid=null)
{
$prefix = C('DB_PREFIX');
if ($uid)
{
$A = $prefix.'warehouse_name A';
$B = $prefix.'user_stock B';
$vo = $this->query("SELECT A.*,A.name title,B.uid
					FROM $A LEFT JOIN $B ON (A.id=B.warehouse_id AND B.uid=$uid)
					WHERE A.status=1 ORDER BY A.seq ASC");
}
else
{
$A = $prefix.'warehouse_name A';
$vo = $this->query("SELECT A.*,A.name title
					FROM $A WHERE A.status=1 ORDER BY A.seq ASC");
}
foreach ($vo as &$v)
{
if (isset($v['uid']) &&$v['uid'] == $uid)
{
$v['_checked'] = true;
}
}
return $vo;
}
public function getAccessFields($module)
{
$uid = session(C('USER_AUTH_KEY'));
$prefix = C('DB_PREFIX');
$vo = S('USER_'.$uid.'_ACCESS_FIELDS');
if( !$vo ||!isset($vo[$module]) )
{
$result = isset($vo)?$vo:array();
$moduleVo = M('Modules')->where(array('module'=>$module))->find();
$module_id = intval($moduleVo['id']);
if($module_id <1) 
{
$result[$module]['default']['all'] = true;
$result[$module]['costom']['all'] = true;
}
else 
{
$A = $prefix.'role_user A';
$B = $prefix.'role B';
$Sql = "SELECT A.role_id FROM $A LEFT JOIN $B ON (A.role_id=B.id) 
				WHERE A.user_id=$uid AND B.status=1 ORDER BY B.seq ASC";
$model = M('');
$vo = $model->query($Sql);
foreach ($vo as $v)
{
$A = $prefix.'role_access A';
$B = $prefix.'fields B';
$Sql = "SELECT B.title,B.field FROM $A LEFT JOIN $B ON(A.field_id=B.id) 
						WHERE A.role_id=".$v['role_id']." AND A.module_id=$module_id GROUP BY A.field_id";
$defaultFields = $model->query($Sql);
if($defaultFields == null)
{
$result[$module]['default']['all'] = true;
}
else 
{
foreach ($defaultFields as $val)
{
$result[$module]['default']['fields'][$val['field']] = true;
}
}
$A = $prefix.'role_access_custom_fields A';
$B = $prefix.'customfields B';
$Sql = "SELECT B.title,B.dbfield FROM $A LEFT JOIN $B ON(A.field_id=B.id) 
						WHERE A.role_id=".$v['role_id']." AND A.module_id=$module_id GROUP BY A.field_id";
$costomFields = $model->query($Sql);
if($costomFields == null)
{
$result[$module]['costom']['all'] = true;
}
else 
{
foreach ($costomFields as $val)
{
$result[$module]['costom']['fields'][$val['dbfield']] = true;
}
}
}
}
S('USER_'.$uid.'_ACCESS_FIELDS',$result,3600);
$vo = $result;
}
return $vo[$module];
}
}?>