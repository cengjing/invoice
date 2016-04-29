<?php

class PriceAction extends WorkflowAction  
{
public function index()
{
$this->setTitle('价格管理');
$this->_index();
}
public function add()
{
$this->setTitle('价格策略');
$this->display();
}
public function insert()
{
$category_id = $_REQUEST['category_id'];
$brand_id = $_REQUEST['brand_id'];
if (empty($category_id) &&empty($brand_id)){
$this->error('请至少选择一个商品分类或者品牌');
}
$this->validate();
if(!isset($_REQUEST['id'])){
$_POST['uid'] = $this->uid;
}
$model = D('Price');
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
public function show()
{
$model = M('Price');
$id = $_REQUEST [$model->getPk ()];
$vo = $model->getById ( $id );
$this->assign ( 'vo',$vo );
$this->setTitle('价格策略-'.$vo['abstract']);
if($vo['brand_id'] >0){
$map['brand_id'] 	= $vo['brand_id'];
}
if($vo['category_id'] >0){
$map['category_id'] = $vo['category_id'];
}
$map['on_sale'] 	= 1;
$model = M('Products');
$count = $model->where($map)->count();
$this->assign ( 'count',$count );
$this->display ();
}
public function edit()
{
$this->setTitle('价格策略');
$this->_edit();
}
public function afterApprove($success)
{
$id = intval($_REQUEST['id']);
$model = D('Price');
$vo = $model->where("id=$id")->find();
$hl = $vo['highs_lows'];
$ratio = $vo['ratio'];
$add_price = $vo['add_price'];
$add_price = (($add_price>0)?"+":"-").floatval($add_price);
$category_id = $vo['category_id'];
$brand_id = $vo['brand_id'];
$where = ' WHERE `on_sale`=1 ';
if($vo['category_id'] >0){
$where .= " AND `category_id`='$category_id'";
}
if($vo['brand_id'] >0){
$where .= " AND `brand_id`='$brand_id'";
}
$set = '';
if($hl == 1){
$set = 'unit_price=unit_price*(1+'.$ratio*0.01.')'.$add_price;
}else{
$set = 'unit_price=unit_price*(1-'.$ratio*0.01.')'.$add_price;
}
$sql = "UPDATE __TABLE__ SET $set $where ";
M('Products')->execute($sql);
$model->where("id=$id")->save(array('status'=>5));
}
}?>