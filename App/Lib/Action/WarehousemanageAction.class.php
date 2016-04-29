<?php

class WarehousemanageAction extends GlobalAction 
{
public function index()
{
$this->setTitle('仓库列表');
$vo = M('WarehouseName')->order('seq ASC')->select();
$this->assign('lists',$vo);
$this->display();
}
public function add()
{
$this->setTitle('新建仓库');
$this->display();
}
public function edit()
{
$id = intval($_REQUEST['id']);
$vo = M('WarehouseName')->where("id=$id")->find();
if ($vo == null) {
$this->error('您选择了错误的仓库');
}
$this->assign('vo',$vo);
$this->setTitle('修改仓库_'.$vo['name']);
$this->display();
}
public function show()
{
$id = intval($_REQUEST['id']);
$vo = M('WarehouseName')->where("id=$id")->find();
if ($vo == null) {
$this->error('您选择了错误的仓库');
}
$this->assign('vo',$vo);
$this->setTitle('仓库_'.$vo['name']);
$this->display();
}
public function insert()
{
$this->validate();
$_POST['on_sale'] = isset($_REQUEST['on_sale'])?1:0;
$_POST['mrp'] = isset($_REQUEST['mrp'])?1:0;
$_POST['status'] = isset($_REQUEST['status'])?1:0;
$model = D('WarehouseName');
$dbfield = $_REQUEST['dbfield'];
$map['dbfield'] = $dbfield;
$vo = $model->where($map)->select();
if($vo !== null)
{
$this->ajaxReturn('','仓库字段名不能重复。',0);
}
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
$ret = M('Warehouse')->query("ALTER TABLE __TABLE__ ADD $dbfield decimal(10,2) NOT NULL default '0.00'");
if(false === $ret)
{
$this->ajaxReturn('','新建数据库字段名失败，请查看数据库是否有ALTER权限。',0);
}
import('ORG.Io.Dir');
Dir::del( getcwd()."/App/Runtime/Data/_fields/");
$id = $model->add ();
}
if ($id !== false) {
$result['id'] = $id;
S('WarehouseName',null);
$this->ajaxReturn ( $result,'',1);
}else {
$this->ajaxReturn ( '','新增失败!',0);
}
}
public function update()
{
$this->insert();
}
public function saveSeq()
{
$ids = $_REQUEST['id'];
$seqs = $_REQUEST['seq'];
$model = M('WarehouseName');
foreach ($ids as $k=>$v){
$data = array(
'id'=>$v,
'seq'=>$seqs[$k],
);
$model->save($data);
}
$this->success('已保存排序。');
}
}
?>