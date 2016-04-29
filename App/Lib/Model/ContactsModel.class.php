<?php

class ContactsModel extends BaseModel 
{
protected $permModel = 'Accounts';
protected $_validate = array (
array ('name','require','请填写客户姓名。'),
array ('company_id','require','请选择客户单位。'),
);
protected $_auto = array (
array ('company','trim',Model::MODEL_BOTH,'function'),
array ('name','trim',Model::MODEL_BOTH,'function'),
array ('cTime','time',Model::MODEL_INSERT,'function'),
array ('status','1'),
);
public function getInfoById( $id )
{
$prefix = C('DB_PREFIX');
$A = $prefix.'contacts A';
$B = $prefix.'accounts B';
$vo = M()->query("SELECT A.*,B.company FROM $A 
							LEFT JOIN $B ON A.company_id=B.id
							WHERE A.id=$id");
return $vo[0];
}
public function getInfo ($id)
{
$vo = $this->where("id=$id")->find();
if($vo === null)
{
return null;
}
$company_id = $vo['company_id'];
$ret['base'] = $vo;
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['owner'][] = $vo['assignto'];
$ret['base']['assignto'] = getUserInfoById($vo['assignto']);
$ret['base']['company'] = $vo['company'];
return $ret;
}
public function stat($id)
{
$model = M('Salesorder');
$vo = $model->query("SELECT SUM(total-collection) sum FROM __TABLE__ WHERE contact_id=$id AND status=3 AND collection<total");
if(empty($vo[0]['sum']))$vo[0]['sum'] = 0;
$ret['arrears'] = $vo[0]['sum'];
$vo = $model->query("SELECT SUM(total-invoice) sum FROM __TABLE__ WHERE contact_id=$id AND status=3 AND invoice<total");
if(empty($vo[0]['sum']))$vo[0]['sum'] = 0;
$ret['invoice'] = $vo[0]['sum'];
return $ret;
}
}?>