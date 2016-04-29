<?php

class CreditAction extends GlobalAction 
{
public function index()
{
$ret = $this->getFilter('Credit');
$A = $this->prefix .'credit A';
$B = $this->prefix .'accounts B';
$from 		= " FROM $A ";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*) $from $where";
$pageSQL 	= " SELECT A.*,B.company $from LEFT JOIN $B ON (A.company_id=B.id)$where ORDER BY id DESC";
$this->_pageList ( $countSQL,$pageSQL );
$this->setTitle('客户信用列表');
$this->display();
}
public function add()
{
$this->setTitle('新建信用政策');
$data['status'] = 1;
$this->assign('data',$data);
$this->display();
}
public function edit()
{
$this->setTitle('修改信用政策');
$id = intval($_REQUEST['id']);
$vo = D('Credit')->getInfo($id);
$this->assign('vo',$vo['base']);
$this->display();
}
public function show()
{
$this->setTitle('查看信用政策');
$id = intval($_REQUEST['id']);
$vo = D('Credit')->getInfo($id);
$this->assign('vo',$vo['base']);
$this->display();
}
public function insert()
{
$this->validate();
$company_id = intval($_REQUEST['company_id']);
$model = D('Credit');
$map['status']		= 1;
$map['company_id'] 	= $company_id;
if($_POST['id']){
$map['id'] = array('neq',$_POST['id']);
}
$vo = $model->where($map)->find();
if(NULL != $vo )
{
$this->ajaxReturn('','已经存在的信用策略，不能重复制定',0);
}
if (false === $model->create ()) {
$this->ajaxReturn ('',$model->getError (),0 );
}
if(isset($_POST['id'])){
$model->save ();
$id = $_POST['id'];
}else{
$id = $model->add ();
}
$data['id'] = $id;
$this->ajaxReturn($data,'保存成功',1);
}
public function update()
{
$this->insert();
}
}
?>