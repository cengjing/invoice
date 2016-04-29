<?php

class ReturnsModel extends BaseModel 
{
protected $_validate = array (
array ('abstract','require','请请填写退货摘要！'),
);
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
protected $_status = array (
'1'=>'已保存',
'2'=>'等待审核',
'3'=>'已审核',
'4'=>'退回审核',
'5'=>'完成',
'6'=>'删除',
);
public function getInfo ($id)
{
$vo = $this->where("id=$id")->find();
$order_id = $vo['order_id'];
$ret['owner'][] = $vo['uid'];
$ret['base'] = $vo;
$vo = M('Salesorder')->where("id=$order_id")->find();
$company_id = $vo['company_id'];
$contact_id = $vo['contact_id'];
$ret['owner'][] = $vo['assignto'];
$ret['owner'][] = $vo['uid'];
$ret['order'] = $vo;
$vo['assignto'] = getUserInfoById($vo['assignto']);
$vo['uid'] = getUserInfoById($vo['uid']);
$vo = M('Accounts')->where("id=$company_id")->find();
$ret['account'] = $vo;
$ret['owner'][] = $vo['assignto'];
$ret['base']['company'] = $vo['company'];
$ret['base']['company_id'] = $vo['id'];
$vo = M('Contacts')->where("id=$contact_id")->find();
$ret['contact'] = $vo;
$ret['base']['contact'] = $vo['name'];
$ret['base']['contact_id'] = $vo['id'];
return $ret;
}
public function getDetails($id)
{
$prefix = C('DB_PREFIX');
$A = $prefix.'returns_details A';
$B = $prefix.'products B';
$details = M()->query("SELECT A.*,B.productname,B.catno,B.description,B.ch_description,B.unit,B.spec FROM $A
			LEFT JOIN $B ON A.product_id=B.id
			WHERE returns_id=$id");
return $details;
}
public function finish($id)
{
$data['id'] = $id;
$data['status'] = 5;
$this->save($data);
$recordModel = D('Record');
$recordModel->insert($id,"退货单$id入库",false,'Returns');
$vo = $this->where("id=$id")->find();
$order_id = $vo['order_id'];
$recordModel->insert($order_id,"退货单$id入库",true,'Salesorder');
D('Salesorder')->autoCheck($order_id);
}
}?>