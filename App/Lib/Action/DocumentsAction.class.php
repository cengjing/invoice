<?php

class DocumentsAction extends GlobalAction 
{
public function index()
{
$ret = $this->getFilter('Documents','A.');
$A = $this->prefix .'documents A ';
$from 		= " FROM $A";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*) $from $where";
$pageSQL 	= " SELECT A.* $from $where ORDER BY id DESC";
$this->_pageList ( $countSQL,$pageSQL );
$this->setTitle('文档列表');
$this->display();
}
public function add()
{
$data['status'] = 1;
$this->setTitle('新增文档');
$this->assign('data',$data);
$this->display();
}
public function edit()
{
$this->setTitle('修改文档');
$id = intval($_REQUEST['id']);
$vo = M('Documents')->where("id=$id")->find();
$this->assign('vo',$vo);
$this->display();
}
public function insert()
{
$this->validate();
$_POST['status'] = isset($_REQUEST['status'])?1:0;
if(!isset($_REQUEST['id']))
{
$_POST['uid'] = $this->uid;
}
if($_REQUEST['cTime'] != '')
{
$date = explode('-',$_REQUEST['cTime']);
$_POST['cTime'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
}
else
{
$_POST['cTime'] = time();
}
$model = D('Documents');
if(!isset($_REQUEST['id']))
{
$_POST['uid'] = $this->uid;
}
if (false === $model->create ()) {
$this->error($model->getError ());
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
if ($id !== false) {
$result['id'] = $id;
$this->redirect('documents/show',array('id'=>$id));
}else {
$this->error ( '新增失败!');
}
}
public function update()
{
$this->insert();
}
public function show()
{
$name = $this->getActionName ();
$model = M($name);
$id = $_REQUEST [$model->getPk ()];
$vo = $model->getById ( $id );
$this->assign ( 'vo',$vo );
$this->setTitle($vo['title'].' - 文档');
$this->display ();
}
}
?>