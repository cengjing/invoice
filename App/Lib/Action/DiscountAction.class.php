<?php

class DiscountAction extends GlobalAction 
{
public function index()
{
$ret = $this->getFilter('Discount');
$A = $this->prefix .'discount A';
$B = $this->prefix .'accounts B';
$from 		= " FROM $A ";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*) $from $where";
$pageSQL 	= " SELECT A.*,B.company $from LEFT JOIN $B ON (A.company_id=B.id)$where ORDER BY id DESC";
$this->_pageList ( $countSQL,$pageSQL );
$this->setTitle('折扣列表');
$this->display();
}
public function add()
{
$this->setTitle('新建折扣');
$data['status'] = 1;
$this->assign('data',$data);
$this->display();
}
public function edit()
{
$this->setTitle('修改折扣');
$id = intval($_REQUEST['id']);
$vo = D('Discount')->getInfo($id);
$this->assign('vo',$vo['base']);
$this->display();
}
public function insert()
{
$this->validate();
$company_id = intval($_REQUEST['company_id']);
$type_id = intval($_REQUEST['type_id']);
$catno = $_REQUEST['catno'];
if(empty($catno) &&empty($type_id))
{
$this->ajaxReturn('','请填写商品编号或者商品分类',0);
}
if(!empty($catno))
{
$map['catno'] = $catno;
$vo = M('Products')->where($map)->find();
if(null == $vo)
{
$this->ajaxReturn('','请填写正确的商品编号',0);
}
$product_id = $map['product_id'];
}
unset($map);
$model = D('Discount');
$map['status']		= 1;
if(!empty($company_id)) $map['company_id'] 	= $company_id;
if(!empty($product_id)) $map['product_id']	= $product_id;
if($_POST['id']){
$map['id'] = array('neq',$_POST['id']);
}
if(empty($product_id))
{
if(!empty($type_id)) $map['type_id']	= $type_id;
}
$vo = $model->where($map)->find();
if(NULL != $vo )
{
$this->ajaxReturn('','已经存在的折扣规则，不能重复制定',0);
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
public function show()
{
$this->setTitle('查看折扣');
$id = intval($_REQUEST['id']);
$vo = D('Discount')->getInfo($id);
$this->assign('vo',$vo['base']);
$this->display();
}
}?>