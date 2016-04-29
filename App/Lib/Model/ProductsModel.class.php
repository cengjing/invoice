<?php

class ProductsModel extends Model 
{
protected $_validate = array (
array ('productname','require','请填写商品名称！'),
array ('catno','require','请填写商品编码！'),
array ('catno','','商品编码必须唯一存在！',0,'unique',Model::MODEL_BOTH ),
);
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
protected $_isSale = array (
'1'=>'是',
'2'=>'否',
);
protected $_isMrp = array (
'1'=>'是',
'2'=>'否',
);
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
$ret['base'] = $vo;
$ret['base']['vender'] = getVenderName($vo['vender_id']);
return $ret;
}
public function getDiscount($id,$company_id=null)
{
$ds = 0;
$map['id'] = $id;
$vo = $this->where($map)->find();
$category_id = $vo['category_id'];
$data['price'] = $vo['unit_price'];
unset($map);
$map['product_id'] = $id;
$model = M('Discount');
$map['company_id'] = intval($company_id);
$vo = $model->where($map)->find();
if($vo != null){
$ds = $vo['discount'];
}else{
unset($map['product_id']);
$map['type_id'] = $category_id;
$vo = $model->where($map)->find();
if($vo != null){
$ds = $vo['discount'];
}
}
$data['ds'] = $ds;
return $data;
}
public function getIsSale()
{
foreach ($this->_isSale as $k=>$v)
{
$ret[] = array(
'id'=>$k,
'title'=>$v
);
}
return $ret;
}
public function getIsMrp()
{
foreach ($this->_isMrp as $k=>$v)
{
$ret[] = array(
'id'=>$k,
'title'=>$v
);
}
return $ret;
}
}?>