<?php

class QuotesAction extends WorkflowAction  
{
public function index()
{
$this->setTitle('报价单列表');
$ret = $this->getFilter('Quotes','A.');
$A = $this->prefix .'quotes A ';
$B = $this->prefix .'user B ';
$C = $this->prefix .'user C ';
$D = $this->prefix .'accounts D ';
$E = $this->prefix .'contacts E ';
$from 		= " FROM $A ";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.name,C.name actBy,D.company,E.name contact $from
						LEFT JOIN $B ON A.assignto=B.id
						LEFT JOIN $C ON A.uid=C.id
						LEFT JOIN $D ON A.company_id=D.id
						LEFT JOIN $E ON A.contact_id=E.id $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->display();
}
public function _before_add()
{
$this->assign('title','新建销售报价');
$contact_id = intval($_REQUEST['contact_id']);
if($contact_id >0){
$vo = D('Contacts')->getPerm($contact_id);
$ret['company'] = $vo['base']['company'];
$ret['company_id'] = $vo['base']['company_id'];
$ret['contact'] = $vo['base']['name'];
$ret['contact_id'] = $vo['base']['id'];
$ret['assignto'] = $vo['account']['assignto'];
$this->assign('data',$ret);
}
}
public function add()
{
$this->display();
}
public function insert()
{
$this->validate();
$gp = array();
$sum = 0;
$total = 0;
$other = 0;
foreach ($_REQUEST['pid'] as $k=>$v){
$data = array();
$data['product_id'] 	= $v;
$data['unit_price']	 	= floatval($_REQUEST['unit_price'][$k]);
$data['qty'] 			= floatval($_REQUEST['qty'][$k]);
$data['sale_price'] 	= floatval($_REQUEST['sale_price'][$k]);
$data['discount'] 	= floatval($_REQUEST['discount'][$k]);
$data['other_price'] 	= floatval($_REQUEST['other_price'][$k]);
$data['sum_price'] 		= $data['qty'] * ($data['sale_price'] +$data['other_price']);
$sum += $data['qty'] * $data['sale_price'];
$other += $data['other_price'];
$total += $data['sum_price'];
$gp[] = $data;
}
if (count($gp) == 0)
{
$this->ajaxReturn('','请填写订单明细。',0);
}
$_POST['uid'] = $this->uid;
$_POST['other_fund'] = $other;
$_POST['sum'] = $sum;
$_POST['total'] = $total;
$model = M('QuotesDetails');
$model->startTrans();
$id = $this->save('Quotes');
if ($id !== false) {
$model->where("quotes_id=$id")->delete();
foreach ($gp as &$v){
$v['quotes_id'] = $id;
$ret = $model->add($v);
if($ret == false)
{
$model->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
}
$result['id'] = $id;
D('Record')->insert($id,'保存报价单',false,$this->getActionName());
if($model->commit())
{
$this->ajaxReturn($result,'',1);
}
}else {
$this->error ( '新增失败!');
}
}
public function update()
{
$this->insert();
}
public function _before_show()
{
$id = intval($_REQUEST['id']);
$model = D('Quotes');
$vo = $model->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->assign('vo',$vo['base']);
$details = $model->getDetails($id);
$this->assign('details',$details);
}
public function show()
{
$vo = $this->__get('vo');
$this->assign('title',$vo['abstract'].'_'.$vo['id'].'_报价单');
$this->display();
}
public function edit() 
{
$this->_before_add();
$this->_before_show();
$this->assign('title','修改销售报价');
$this->display ();
}
public function transfer()
{
$id = intval($_REQUEST['id']);
$M = D('Quotes');
$vo = $M->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$info = $vo['base'];
if($info['status'] != 1)
{
$this->error ('这张报价单已不能再转为订单');
}
$M->startTrans();
$data['company_id']		=	$info['company_id'];
$data['contact_id']		=	$info['contact_id'];
$data['assignto']		=	$info['assignto'];
$data['abstract']		=	$info['abstract'];
$data['notes']			=	$info['notes'];
$data['other_fund']		=	$info['other_fund'];
$data['freight']		=	$info['freight'];
$data['sum']			=	$info['sum'];
$data['total']			=	$info['total'];
$data['cTime']			=	time();
$data['uid']			=	$info['uid'];
$data['status']			=	1;
$data['quotes_id']		=	$id;
$model = M('Salesorder');
$order_id = $model->add ($data);
$Record = D('Record');
$Record->insert($order_id,'报价单'.$id.'转存销售订单。',false,'Salesorder');
$Record->insert($id,'报价单转存销售订单'.$order_id,false,'Quotes');
if(intval($id)<100000000)
{
$new_id = intval($order_id)+100000000;
$model->execute("UPDATE __TABLE__ SET id=$new_id WHERE id=$order_id");
$order_id = $new_id;
}
$model = M('SalesorderDetails');
$vo = M('QuotesDetails')->where("quotes_id=$id")->select();
foreach ($vo as $v){
$data = array();
$data['order_id']	=	$order_id;
$data['product_id']	=	$v['product_id'];
$data['qty']		=	$v['qty'];
$data['unit_price']	=	$v['unit_price'];
$data['sale_price']	=	$v['sale_price'];
$data['sum_price']	=	$v['sum_price'];
$data['other_price']=	$v['other_price'];
$ret = $model->add ($data);
if($ret == false)
{
$M->rollback();
$this->error ('记录明细失败!');
}
}
$data = array();
$data['id']			=	$id;
$data['status']		=	5;
$data['order_id']	=	$order_id;
$ret = $M->save($data);
if($ret == false)
{
$M->rollback();
$this->error ('报价单转换销售订单失败!');
}
if($M->commit())
{
$this->redirect("salesorder/show",array('id'=>$order_id));
}
}
public function delete()
{
$id = intval($_REQUEST['id']);
$model = D('Quotes');
$vo = $model->getPerm($id);
if($vo === null)
{
$this->error('数据库中并没有这张单据。');
}
if($vo === false)
{
$this->error('您没有查看这张单据的权限。');
}
parent::delete();
}
}?>