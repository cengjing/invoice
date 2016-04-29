<?php

class CustomfieldsAction extends GlobalAction 
{
public function index()
{
$this->setTitle('自定义字段');
$vo = M('Modules')->order('seq ASC')->select();
$this->assign('modules',$vo);
$this->display();
}
public function show()
{
$id = intval($_REQUEST['id']);
$this->assign('pid',$id);
$list = M('Customfields')->where("module_id=$id")->order('seq asc')->select();
$this->assign('list',$list);
$vo = M('Modules')->where("id=$id")->find();
$this->setTitle($vo['title'].' - 字段列表');
$this->display();
}
public function add()
{
$module_id = intval($_REQUEST['module_id']);
$this->assign('id',$module_id);
$data['status'] = 1;
$data['module_id'] = intval($_REQUEST['module_id']);
$this->assign('data',$data);
$this->setTitle('添加字段');
$this->display();
}
public function insert()
{
if(isset($_POST['id']))
{
$this->validate('','edit');
}
else 
{
$this->validate();
}
$CF = D('CustomFields');
if(!isset($_POST['id']))
{
import('ORG.Util.String');
$model = $CF;
$repeat = true;
while ($repeat)
{
$dbfield = String::randString(6,3);
$vo = $model->where(array('dbfield'=>$dbfield))->find();
if($vo == null)$repeat = false;
}
$_POST['dbfield'] = $dbfield;
}
$mid = $_REQUEST['module_id'];
$length = intval($_REQUEST['len']);
$_POST['status'] = isset($_REQUEST['status'])?1:0;
if($length<=0 ||$length>255)
{
$this->ajaxReturn('','字段的存储长度,只能在0-255之间',0);
}
$vo = M('Modules')->where("id=$mid")->find();
$model = M($vo['module']);
if(isset($_POST['id']))
{
if($vo['length'] != $length)
{
$vo = $CF->where(array('id'=>$_POST['id']))->find();
$dbfield = $vo['dbfield'];
$sql = "ALTER TABLE __TABLE__ CHANGE $dbfield $dbfield varchar($length)";
$result = $model->query($sql);
if ($result === false)
{
$this->ajaxReturn('','修改数据库字段失败'.$sql,0);
}
}
}
else 
{
$vo = $model->query('SHOW COLUMNS FROM '.$this->prefix.$vo['module']);
foreach ($vo as $v)
{
if($v['Field'] == $dbfield)
{
$this->ajaxReturn('','创建数据库字段失败,字段('.$dbfield.')已经存在不能重复添加',0);
break;
}
}
$result = $model->query("ALTER TABLE __TABLE__ ADD $dbfield varchar($length)");
if(false === $result)
{
$this->ajaxReturn('','创建数据库字段失败,原因:',$model->getLastSql(),0);
}
}
$model = $CF;
if (false === $model->create ()) 
{
$this->ajaxReturn('',$model->getError (),0);
}
if(isset($_POST['id']))
{
$model->save ();
$id = $_REQUEST['id'];
}
else 
{
$id = $model->add ();
}
if ($id !== false) 
{
$this->clearCache();
$result['id'] = $mid;
$this->ajaxReturn($result,'',1);
}else {
$this->ajaxReturn('','添加失败',0);
}
}
private function clearCache()
{
import('ORG.Io.Dir');
$path = getcwd()."/App/Runtime/Data/_fields";
Dir::del($path);
}
public function edit() 
{
$id = intval($_REQUEST ['id']);
$vo = M('Customfields')->where("id=$id")->find();
$this->assign('vo',$vo);
$this->setTitle('修改字段 - '.$vo['title']);
$this->display();
}
public function remove()
{
$id = $_REQUEST['id'];
$model = D ( 'CustomFields');
$vo = $model->where("id=$id")->find();
if(false == $vo){echo -1;return ;}
$field = $vo['dbfield'];
$result = M($vo['module'])->query("ALTER TABLE __TABLE__ DROP $field");
if(false === $result){echo -1;return ;}
$model->where("id=$id")->delete();
echo 1;
}
}
?>