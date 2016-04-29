<?php

class VenderAction extends GlobalAction  
{
public function index()
{
$this->setTitle('供货商列表');
$this->_getVender();
$this->display();
}
public function filter()
{
if(!$this->isAjax())return;
$this->_getVender();
$this->display();
}
public function insert()
{
$this->validate();
$_POST['status'] = isset($_REQUEST['status'])?1:0;
$model = D('Vender');
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
if ($id !== false) {
$result['id'] = $id;
$this->ajaxReturn ( $result,'',1);
}else {
$this->ajaxReturn ( '','新增失败!',0);
}
}
public function update()
{
$this->insert();
}
private function _getVender()
{
$ret = $this->getFilter('Vender','A.');
$A = $this->prefix .'vender A ';
$from 		= " FROM $A ";
$where 		= ($ret['where']=='')?'':(' WHERE '.$ret['where']);
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.* $from $where ORDER BY A.company ASC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
}
public function show()
{
$id = $_REQUEST['id'];
$model = M ('Vender');
$vo = $model->getById ( $id );
$this->assign ( 'vo',$vo );
$this->setTitle($vo['company']);
$this->display();
}
public function getById()
{
if(!$this->isAjax())return;
$id = intval($_REQUEST['id']);
$vo = M('Vender')->where("id=$id")->find();
$this->ajaxReturn($vo,'',1);
}
public function add()
{
$data['status'] = 1;
$this->assign('data',$data);
$this->setTitle('新建供货商');
$this->display();
}
public function _before_edit()
{
$this->setTitle('修改供货商');
}
public function edit()
{
$this->_edit();
}
}?>