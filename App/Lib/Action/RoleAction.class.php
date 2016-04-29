<?php

class RoleAction extends GlobalAction 
{
public function index()
{
$this->setTitle('角色列表');
$vo = M('Role')->order('seq ASC')->select();
$this->assign('list',$vo);
$this->display();
}
private function _saveAccess($id)
{
$nodes = $_REQUEST['item'];
$access = M('Access');
$node = M('Node');
$allNodes = $node->where(array('status'=>1,'level'=>3,'bind'=>array('neq',0)))->select();
$map['role_id'] = $id;
$access->where($map)->delete();
foreach ($nodes as $v){
if($v){
$map['node_id'] = $v;
$access->add($map);
foreach ($allNodes as $val){
if ($val['bind'] == $v){
$map['node_id'] = $val['id'];
$access->add($map);
}
}
}
}
$pid = $node->where ('pid=0')->field ('id')->find ();
$map['node_id'] = $pid['id'];
$access->add($map);
}
public function editAccessFields()
{
$role_id = intval($_REQUEST['role_id']);
$vo = M('Role')->where("id=$role_id")->find();
$this->assign('vo',$vo);
$vo = M('Modules')->order('seq ASC')->select();
$this->assign('modules',$vo);
$this->setTitle('修改角色字段权限');
$this->display('editFields');
}
public function saveFields()
{
if(!$this->isAjax())return;
$module_id 	= intval($_REQUEST['module_id']);
$role_id	= intval($_REQUEST['id']);
$model = M('RoleAccess');
$con['module_id'] = $module_id;
$con['role_id'] = $role_id;
$model->where($con)->delete();
$fields = $_REQUEST['fields'];
foreach ($fields as $v)
{
$map = array();
$map['field_id'] 	= $v;
$map['role_id'] 	= $role_id;
$map['module_id'] 	= $module_id;
$model->add($map);
}
$model = M('RoleAccessCustomFields');
$model->where($con)->delete();
$costomFields = $_REQUEST['costomFields'];
foreach ($costomFields as $v)
{
$map = array();
$map['field_id'] 	= $v;
$map['role_id'] 	= $role_id;
$map['module_id'] 	= $module_id;
$model->add($map);
}
$this->ajaxReturn('','修改成功',1);
}
public function getFields()
{
if(!$this->isAjax())return;
$module_id 	= intval($_REQUEST['module_id']);
$role_id	= intval($_REQUEST['role_id']);
$this->assign('module_id',$module_id);
$vo = M('Modules')->where("id=$module_id")->find();
$moduleName = $vo['module'];
$map['module'] = $moduleName;
$map['level'] = 2;
$vo = M('Fields')->field('id,title')->where($map)->order('seq ASC')->select();
$access = M('RoleAccess')->where("`role_id`='$role_id' AND `module_id`='$module_id'")->select();
foreach ($vo as &$val)
{
foreach ($access as $v)
{
if ($val['id'] == $v['field_id'])
{
$val['check'] = true;
break;
}
}
}
$this->assign('defaultFields',$vo);
$vo = M('Customfields')->field('id,title')->where("module_id=$module_id")->order('seq ASC')->select();
$access = M('RoleAccessCustomFields')->where("`role_id`='$role_id' AND `module_id`='$module_id'")->select();
foreach ($vo as &$val)
{
foreach ($access as $v)
{
if ($val['id'] == $v['field_id'])
{
$val['check'] = true;
break;
}
}
}
$this->assign('customFields',$vo);
$this->display('fields');
}
public function editActionControl()
{
$role_id = intval($_REQUEST['role_id']);
$vo = M('Role')->where("id=$role_id")->find();
$this->assign('vo',$vo);
$A = $this->prefix.'node A';
$B = $this->prefix.'access B';
$Sql = "SELECT A.*,B.role_id FROM $A 
							LEFT JOIN $B ON (A.id=B.node_id AND B.role_id=$role_id)
							WHERE A.pid!=0 AND bind=0 ORDER BY seq ASC";
$vo = M()->query($Sql);
$tree = list_to_tree ( $vo,'id','pid','_child',1 );
$this->assign('tree',$tree);
$this->setTitle('修改操作权限');
$this->display('editAction');
}
public function saveAction()
{
if(!$this->isAjax())return;
$id = intval($_REQUEST['id']);
$this->_saveAccess($id);
$this->ajaxReturn('','修改成功',1);
}
public function saveSeq()
{
$ids = $_REQUEST['id'];
$seqs = $_REQUEST['seq'];
$model = M('Role');
foreach ($ids as $k=>$v){
$data = array(
'id'=>$v,
'seq'=>$seqs[$k],
);
$model->save($data);
}
$this->success('已保存角色排序。');
}
}?>