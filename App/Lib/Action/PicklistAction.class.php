<?php

class PicklistAction extends GlobalAction  
{
public function index()
{
$vo = M('Picklist')->order('seq ASC')->select();
$this->assign('modules',$vo);
$this->assign('title','自定义选项');
$this->display();
}
public function show()
{
$model = M('PicklistDetails');
$id = intval($_REQUEST ['id']);
$vo = M('Picklist')->where("id=$id")->find();
$this->assign('title',$vo['title'].' - 选项');
$this->assign('currentItem',$vo['title']);
$this->assign('pid',$id);
$vo = $model->where("pick_id=$id")->order('seq ASC')->select();
$tree = list_to_tree ( $vo,'id','pid','_child');
$list = array ();
tree_to_array ( $tree,0,$list,'├-',true);
$this->assign ( 'list',$list );
$this->display ();
}
public function add()
{
$id = $_REQUEST ['pick_id'];
$vo = M('Picklist')->where("id=$id")->find();
$this->assign('multi',$vo['level']);
$data['status'] = 1;
$this->assign('data',$data);
$this->assign ( 'pick_id',$id );
$this->setTitle('添加选项');
$this->display();
}
public function edit()
{
$id = $_REQUEST ['id'];
$model = M ( 'PicklistDetails');
$vo = $model->getById($id);
$this->assign ( 'pick_id',$vo ['pick_id'] );
$v = M('Picklist')->where('id='.$vo ['pick_id'])->find();
$this->assign('multi',$v['level']);
$this->assign ( 'vo',$vo );
$this->setTitle('修改选项 - '.$vo['title']);
$this->display ();
}
public function update()
{
$_POST['status'] = isset($_REQUEST['status'])?1:0;
$_POST['def'] = isset($_REQUEST['def'])?1:0;
$pick_id 	= intval($_POST['pick_id']);
$id 		= intval($_POST['id']);
$title		= $_POST['title'];
$model = D ( 'PicklistDetails');
$vo = $model->where("id=$id")->find();
$original = $vo['title'];
$vo = $model->where("pick_id=$pick_id")->order('seq ASC')->select();
$tree = list_to_tree ( $vo,'id','pid','_child');
$list = array ();
tree_to_array ( $tree,0,$list,'');
if(is_mychild($list,$_POST['id'],$_POST['pid']))
$this->error("不能移动到自身的子树下!");
if (false === $model->create ()) 
{
$this->error ( $model->getError () );
}
$result = $model->save ();
if (false !== $result) 
{
if($title != $original)
{
unset($data);
$data['module'] 	= $this->getActionName();
$data['record_id']	= $id;
$data['uid'] 		= $this->uid;
$data['original'] 	= $original;
$data['revise'] 	= $title;
$data['cTime']		= time();
D('EditHistory')->add($data);
}
$this->success ( '编辑成功!');
}else {
$this->error ( '编辑失败!');
}
}
public function insert()
{
$_POST['status'] = isset($_REQUEST['status'])?1:0;
$_POST['def'] = isset($_REQUEST['def'])?1:0;
$pick_id = intval($_REQUEST['pick_id']);
if($pick_id<=0){
$this->error('错误的类别');
}
$model = D('PicklistDetails');
if (false === $model->create ()) {
$this->error ( $model->getError () );
}
$result = $model->add ();
if (false !== $result) {
$this->success ( '添加成功!');
}else {
$this->error ( '添加失败!');
}
}
public function saveSeq()
{
$ids = $_REQUEST['id'];
$seqs = $_REQUEST['seq'];
$model = M('PicklistDetails');
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