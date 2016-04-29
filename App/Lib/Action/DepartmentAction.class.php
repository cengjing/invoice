<?php

class DepartmentAction extends GlobalAction 
{
public function index() 
{
$this->setTitle('部门管理');
$vo = M('Department')->order('seq ASC')->select();
$tree = list_to_tree ( $vo,'id','pid','_child');
$nodes = array ();
tree_to_array ( $tree,1,$nodes,'├-&nbsp;',true );
$this->assign('list',$nodes);
$this->display ();
}
public function edit()
{
$this->setTitle('修改部门信息');
$id = intval($_REQUEST['id']);
$vo = M('Department')->where("id=$id")->find();
$this->assign('vo',$vo);
$this->display();
}
public function insert()
{
$this->validate();
$_POST['status'] = isset($_REQUEST['status'])?1:0;
$model = D('Department');
if (false === $model->create ()) {
$this->ajaxReturn('',$model->getError (),0);
}
if(isset($_REQUEST['id']))
{
$id = intval($_REQUEST['id']);
$model->save();
}
else 
{
$id = $model->add ();
}
if ($id !== false) 
{
$result['id'] = $id;
S('DepartmentInfo',NULL);
$this->ajaxReturn ( $result,'',1);
}else {
$this->ajaxReturn ( '','新增失败!',0);
}
}
public function saveSeq()
{
$ids = $_REQUEST['id'];
$seqs = $_REQUEST['seq'];
$model = M('Department');
foreach ($ids as $k=>$v){
$data = array(
'id'=>$v,
'seq'=>$seqs[$k],
);
$model->save($data);
}
S('DepartmentInfo',NULL);
$this->success('已保存部门排序。');
}
}
?>