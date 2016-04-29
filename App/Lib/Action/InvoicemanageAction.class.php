<?php

class InvoicemanageAction extends GlobalAction 
{
public function index()
{
$this->setTitle('销售发票管理');
$ret = $this->getFilter('Invoice','A.');
$A = $this->prefix .'invoice A ';
$B = $this->prefix .'contacts B ';
$C = $this->prefix .'accounts C ';
$from 		= " FROM $A 
						LEFT JOIN $B ON A.contact_id=B.id 
						LEFT JOIN $C ON B.company_id=C.id";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where ";
$pageSQL 	= " SELECT A.*,B.name contact,C.company $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->display();
}
public function show()
{
$id = intval($_REQUEST['id']);
$model = D('Invoice');
$vo = $model->getInfo($id);
$this->assign('vo',$vo['base']);
$this->setTitle('发票单号_'.$vo['base']['id']);
$details = $model->getDetails($id);
$total = $vo['base']['total'];
$type = $vo['base']['type_id'];
$this->assign('details',$details);
$this->assign('invoice_time',getTime(time()));
$this->display();
}
public function update()
{
$this->validate();
$id = intval($_REQUEST['id']);
$model = D('Invoice');
$map['id'] 		= $id;
$map['status'] 	= 1;
$vo = $model->where($map)->find();
$type = $vo['in_type'];
if(null == $vo)
{
$this->ajaxReturn ('','当前状态不是提交开票状态，不能开票！',0);
}
$_POST['status'] 	= 2;
$_POST['act_uid'] 	= $this->uid;
if(isset($_POST['act_time'])){
$date = explode('-',$_POST['act_time']);
$time = mktime(0,0,0,$date[1],$date[2],$date[0]);
$_POST['act_time'] = $time;
}
if (false === $model->create()) 
{
$this->ajaxReturn ('',$model->getError (),0);
}
$model->save();
if( $type == 1 )
{
if(true)
{
D('AccountReceivable')->autoCreate($vo,1);
}
}
$vo = $model->getDetails($id);
$orders = array();
foreach ($vo as $v)
{
$orders[] = $v['order_id'];
}
$orders = array_unique($orders);
$model = D('Record');
foreach ($orders as $order_id)
{
$model->insert($order_id,'发票申请：'.$id.'，已开票',true,'Salesorder');
}
$this->ajaxReturn('','',1);
}
public function delete()
{
if(isset($_POST['act_time'])){
$date = explode('-',$_POST['act_time']);
$time = mktime(0,0,0,$date[1],$date[2],$date[0]);
$_POST['act_time'] = $time;
}
$id = intval($_REQUEST['id']);
$model = D('Invoice');
$vo = $model->where("id=$id AND status <> 3")->find();
if(null == $vo)
{
$this->ajaxReturn ('','当前发票状态不能被删除！',0 );
}
$type = $vo['in_type'];
$_POST['status'] = 3;
$_POST['act_uid'] = $this->uid;
if (false === $model->create ())
{
$this->ajaxReturn ('',$model->getError (),0 );
}
$model->save();
if($type == 1)
{
M('AccountReceivable')->where(array('from_id'=>$id,'type'=>1,'status'=>3))->save(array('status'=>6));
}else{
}
$order = D('Salesorder');
$vo = M('InvoiceOrder')->where("invoice_id=$id")->select();
$model = D('Record');
foreach ($vo as $v)
{
$order_id = $v['order_id'];
$amount = $v['amount'];
if(intval($order_id) >0)
{
if($type == 1)
{
$order->execute("UPDATE __TABLE__ SET invoice=invoice-$amount WHERE id=$order_id");
$model->insert($order_id,"删除申请蓝字发票金额：$amount",false,'Salesorder');
}else{
$order->execute("UPDATE __TABLE__ SET invoice=invoice+$amount WHERE id=$order_id");
$model->insert($order_id,"删除申请红字发票金额：$amount",false,'Salesorder');
}
$order->autoCheck($order_id);
}
}
$this->ajaxReturn('','',1);
}
}
?>