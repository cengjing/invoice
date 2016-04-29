<?php

class AccountsModel extends BaseModel 
{
protected $_validate = array (
array ('company','require','请填写客户公司名称。'),
array ('province','require','请选择客户所在省份。'),
array ('assignto','require','请选择客户的负责人。'),
);
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
array ('company','trim',Model::MODEL_BOTH,'function'),
array ('address','trim',Model::MODEL_BOTH,'function'),
array ('regioncode','trim',Model::MODEL_BOTH,'function'),
array ('postalcode','trim',Model::MODEL_BOTH,'function'),
array ('status','1'),
);
public function getInfo ($id)
{
$vo = $this->where("id=$id")->find();
$ret['base'] = $vo;
$ret['owner'][] = $vo['assignto'];
return $ret;
}
public function stat($id)
{
$model = M('Salesorder');
$vo = $model->query("SELECT SUM(total-collection) sum FROM __TABLE__ WHERE company_id=$id AND status=3 AND collection<total");
if(empty($vo[0]['sum']))$vo[0]['sum'] = 0;
$ret['arrears'] = formatCurrency($vo[0]['sum']);
$vo = $model->query("SELECT SUM(total-invoice) sum FROM __TABLE__ WHERE company_id=$id AND status=3 AND invoice<total");
if(empty($vo[0]['sum']))$vo[0]['sum'] = 0;
$ret['invoice'] = formatCurrency($vo[0]['sum']);
return $ret;
}
}
?>