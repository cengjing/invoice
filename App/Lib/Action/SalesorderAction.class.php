<?php

class SalesorderAction extends WorkflowAction  
{
public function index()
{
$this->assign('title','销售订单');
$vars = $this->getFilterSql();
$this->_pageList ( $vars['countSQL'],$vars['pageSQL'],$vars['sum'] );
$this->display();
}
private function getFilterSql()
{
$ret = $this->getFilter('Salesorder','A.');
$A = $this->prefix .'salesorder A ';
$B = $this->prefix .'user B ';
$C = $this->prefix .'user C ';
$D = $this->prefix .'accounts D ';
$E = $this->prefix .'contacts E ';
$from 		= " FROM $A
						LEFT JOIN $D ON A.company_id=D.id
						LEFT JOIN $E ON A.contact_id=E.id";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$order 		= " ORDER BY ".($ret['order'] != ''?$ret['order']:'A.id DESC');
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.name,C.name actBy,D.company,E.name contact $from
						LEFT JOIN $B ON A.assignto=B.id
						LEFT JOIN $C ON A.uid=C.id $where $order ";
$vars['countSQL'] 	= $countSQL;
$vars['pageSQL'] 	= $pageSQL;
$vars['sum'] 		= $ret['arr_sum'];
return $vars;
}
public function _before_add()
{
$this->assign('title','新建销售订单');
$contact_id = intval($_REQUEST['contact_id']);
if($contact_id >0){
$vo = D('Contacts')->getPerm($contact_id);
$ret['company'] = $vo['base']['company'];
$ret['company_id'] = $vo['base']['company_id'];
$ret['contact'] = $vo['base']['name'];
$ret['contact_id'] = $vo['base']['id'];
$ret['assignto'] = $vo['account']['assignto'];
$this->assign('vo',$ret);
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
$other = 0;
foreach ($_REQUEST['pid'] as $k=>$v)
{
if(isset($gp[$v]))
$this->ajaxReturn('',"商品ID为".$v."，不能重复。",0);
if(floatval($_REQUEST['qty'][$k])<=0)
$this->ajaxReturn('',"商品ID".$v."，订单数量不正确。".count($_REQUEST['item']),0);
$data = array();
if(isset($_REQUEST['id']))
$data['id']				= intval($_REQUEST['row'][$k]);
$data['product_id'] 	= $v;
$data['unit_price']	 	= floatval($_REQUEST['unit_price'][$k]);
$data['qty'] 			= floatval($_REQUEST['qty'][$k]);
$data['sale_price'] 	= floatval($_REQUEST['sale_price'][$k]);
$data['discount'] 		= floatval($_REQUEST['discount'][$k]);
$data['other_price'] 	= floatval($_REQUEST['other_price'][$k]);
$data['sum_price'] 		= $data['qty'] * ($data['sale_price'] +$data['other_price']);
$sum 	+= $data['sale_price'] * $data['qty'];
$other	+= $data['other_price'] * $data['qty'];
$gp[$v] = $data;
}
if (count($gp) == 0)
$this->ajaxReturn('','请填写订单明细。',0);
if(!isset($_REQUEST['id']))
$_POST['uid'] = $this->uid;
$_POST['other_fund'] = $other;
$_POST['sum'] = $sum;
$_POST['total'] = $sum +$other +$_REQUEST['freight'];
$model = M('SalesorderDetails');
$model->startTrans();
$id = $this->save('Salesorder');
if ($id !== false)
{
$vo = $model->where(array('order_id'=>$id))->field('id')->select();
foreach ($vo as $val)
$ids[] = $val['id'];
foreach ($gp as $v){
if($v['id'] >0)
{
$ret = $model->save($v);
$diff[] = $v['id'];
}else{
unset($v['id']);
$v['order_id'] = $id;
$ret = $model->add($v);
}
if($ret === false)
{
$model->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
}
$diff = array_diff($ids,$diff);
if(count($diff) >0)
{
$map['id'] = array('IN',$diff);
$ret = $model->where($map)->delete();
if($ret === false)
{
$model->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
}
$result['id'] = $id;
D('Record')->insert($id,(isset($_REQUEST['id'])?'修改':'保存').'销售订单',false,$this->getActionName());
if($model->commit())
{
$this->ajaxReturn($result,'',1);
}
}else {
$this->ajaxReturn ('','新增失败!',0);
}
}
public function update()
{
$this->insert();
}
public function _before_show()
{
parent::_before_show();
$id = intval($_REQUEST['id']);
$model = D('Salesorder');
$vo = $model->getPerm($id);
if($vo === null)
{
$this->error('数据库中并没有这张单据。');
}
if($vo === false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->assign('vo',$vo['base']);
$company_id = $vo['base']['company_id'];
if($this->__get('allowApprove') == true)
{
$this->assign('stat',D('Accounts')->stat($company_id));
$this->assign('credit',formatCurrency(D('Credit')->getAccountCredit($company_id)));
}
$details = $model->getDetails($id);
$this->assign('details',$details);
if($this->__get('allowApprove') == true)
{
$model = D('Products');
foreach ($details as $val)
{
$ds = $model->getDiscount($val['product_id'],$company_id);
$discountPrice = $ds['price'] * (1 -$ds['ds'] * 0.01);
if($discountPrice>$val['sale_price'])
{
$val['discount_price'] = formatCurrency($discountPrice);
$val['discount'] = $ds['ds'];
$ret[] = $val;
}
}
$this->assign('discountList',$ret);
}
}
public function show()
{
$vo = $this->__get('vo');
$this->assign('title',$vo['abstract'].'_'.$vo['id'].'_销售订单');;
$this->display();
}
public function advShow()
{
$id = intval($_REQUEST['id']);
$s = D('Salesorder');
$vo = $s->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->assign('title','销售订单-'.$id.'-更多信息');
$model = M();
$this->assign('id',$id);
$A = $this->prefix.'purchase_order_details A';
$B = $this->prefix.'purchase B';
$sql = "SELECT B.* FROM $A LEFT JOIN $B ON (A.purchase_id=B.id) WHERE A.order_id=$id GROUP BY B.id ORDER BY B.id DESC";
$vo = $model->query($sql);
if(count($vo)==0)
$vo = null;
$this->assign('purchase',$vo);
$vo = $s->getDelivery($id);
if(count($vo) == 0)
$vo = null;
$this->assign('delivery',$vo);
$A = $this->prefix.'invoice_order A';
$B = $this->prefix.'invoice B';
$sql = "SELECT B.* FROM $A LEFT JOIN $B ON A.invoice_id=B.id WHERE A.order_id=$id ORDER BY B.cTime DESC";
$vo = $model->query($sql);
if(count($vo)==0)
$vo = null;
$this->assign('invoice',$vo);
$vo = M('Returns')->where("order_id=$id")->order('id DESC')->select();
if(count($vo)==0)
$vo = null;
$this->assign('returns',$vo);
$this->display('adv');
}
public function edit() 
{
$this->_before_show();
$this->assign('title','修改销售订单');
$this->display ();
}
public function duplicate()
{
$this->_before_show();
$this->assign('title','复制销售订单');
$this->display ('add');
}
public function delete()
{
$id = intval($_REQUEST['id']);
$model = D('Salesorder');
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
public function _before_pend()
{
$id = intval($_REQUEST['id']);
$model = D('Salesorder');
$vo = $model->getPerm($id);
if($vo === null)
{
$this->error('数据库中并没有这张单据。');
}
if($vo === false)
{
$this->error('您没有查看这张单据的权限。');
}
$company_id = $vo['base']['company_id'];
$assignto = $vo['base']['assignto'];
$total = $vo['base']['total'];
if(getConfigValue('company_credit') == 1)
{
$vo = $model->query("SELECT SUM(total-collection) sum FROM __TABLE__ WHERE company_id=$company_id AND status=3 AND collection<total");
$sum = $vo[0]['sum'] +$total;
$vo = M('Credit')->where(array('company_id'=>$company_id,'status'=>1))->find();
$maxCredit = (null == $vo)?floatval(getConfigValue('company_credit_value')):$vo['amount'];
if($maxCredit>0 &&$sum>$maxCredit){
$this->error('客户已超出信用额度，不能继续审核订单。');
}
}
if(getConfigValue('company_credit') == 1)
{
$vo = $model->query("SELECT SUM(total-collection) sum FROM __TABLE__ WHERE assignto=$assignto AND status=3 AND collection<total");
$sum = $vo[0]['sum'] +$total;
$vo = M('User')->where(array('id'=>$assignto))->find();
$maxCredit = (null == $vo)?floatval(getConfigValue('user_credit_value')):$vo['credit'];
if($maxCredit>0 &&$sum>$maxCredit){
$this->error('业务员已超出公信用额度，不能继续审核订单。');
}
}
}
public function afterApprove($success)
{
$id = intval($_REQUEST['id']);
if ($success)
{
$vo = M('Salesorder')->where(array('id'=>$id))->find();
$company_id = $vo['company_id'];
D('AccountsStat')->stat($company_id,'salesorder');
}
}
public function export()
{
$vars = $this->getFilterSql();
$pageSQL = $vars['pageSQL'];
$vo = M()->query($pageSQL);
$this->assign('vo',$vo);
$this->display('public:export');
}
}?>