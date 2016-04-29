<?php

class AccountPayableAction extends WorkflowAction 
{
public function index()
{
$ret = $this->getFilter('AccountPayable','A.');
$A = $this->prefix .'account_payable A ';
$B = $this->prefix .'vender B';
$D = $this->prefix .'user D';
$E = $this->prefix .'contacts E ';
$F = $this->prefix .'accounts F ';
$G = $this->prefix .'user G ';
$from 		= " FROM $A 
						LEFT JOIN $B ON A.vender_id=B.id
						LEFT JOIN $D ON A.uid=D.id
						LEFT JOIN $E ON A.contact_id=E.id
						LEFT JOIN $F ON E.company_id=F.id
						LEFT JOIN $G ON A.assignto=G.id";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.company vender,D.name username,E.name contact,F.company,G.name assignto $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->setTitle('应付单列表');
$this->display();
}
public function add()
{
$this->setTitle('新建应付款单');
$this->display();
}
public function show()
{
$id = intval($_REQUEST['id']);
$vo = D('AccountPayable')->getInfo($id);
$this->setTitle('应付款单 - '.$vo['base']['id']);
$this->assign ( 'vo',$vo['base'] );
$this->assign ( 'vender_id',$vo['vender']['id'] );
$this->display();
}
public function edit()
{
$id = intval($_REQUEST['id']);
$vo = D('AccountPayable')->getInfo($id);
$this->setTitle('修改应付款单 - '.$vo['base']['id']);
$this->assign ( 'vo',$vo['base'] );
$this->display();
}
public function insert()
{
$assignto= intval($_REQUEST['assignto']);
$contact_id = intval($_REQUEST['contact_id']);
$vender_id = intval($_REQUEST['vender_id']);
$from_id = intval($_REQUEST['from_id']);
if($assignto ==0 &&$contact_id==0 &&$vender_id==0)
$this->ajaxReturn('','请选择至少一个付款对象',0);
$flag = 0;
if($assignto >0)$flag++;
if($contact_id >0)$flag++;
if($vender_id >0)$flag++;
if($flag != 1)
$this->ajaxReturn('','请选择至少一个付款对象',0);
$this->validate();
if(floatval($_POST['amount'])<=0)
{
$this->ajaxReturn('','请填写金额',0);
}
if(!isset($_REQUEST['id']))
{
$_POST['uid'] = $this->uid;
}
$type = $_REQUEST['type'];
$_POST['loan'] = in_array($type,array(1,2,5,10))?1:-1;
if($type == 1)
{
if($from_id <= 0)
$this->ajaxReturn('','请填写采购发票',0);
$vo = M('Purchaseinvoice')->where(array('id'=>$from_id))->find();
if($vo == null)
$this->ajaxReturn('','采购发票'.$from_id.'并不存在。',0);
if($vo['vender_id'] != $vender_id)
$this->ajaxReturn('','采购发票的供货商与您选择的供货商不匹配，请检验',0);
}
if($type == 5)
{
if($from_id <= 0)
$this->ajaxReturn('','请填写退款订单',0);
$vo = M('Salesorder')->where(array('id'=>$from_id))->find();
if($vo == null)
$this->ajaxReturn('','退款订单'.$from_id.'并不存在。',0);
if($vo['contact_id'] != $contact_id)
$this->ajaxReturn('','销售订单的客户与您选择的销售客户不匹配，请检验',0);
if($vo['collection']<$_POST['amount'])
$this->ajaxReturn('','应付款金额不能大于销售订单的付款金额，请检验',0);
}
$id = $this->save('AccountPayable');
if ($id !== false) {
$result['id'] = $id;
$this->ajaxReturn($result,'',1);
}else {
$this->ajaxReturn ('','新增失败!',0);
}
}
public function update()
{
$this->insert();
}
public function delete()
{
$id = intval($_REQUEST['id']);
$data['status'] = 6;
$map['id'] = $id;
$map['_string'] = 'status=1 OR status=3 OR status=4';
$result = M('AccountPayable')->where($map)->save($data);
if(false !== $result)
{
D('Record')->insert($id,'删除单据。',false,$this->getActionName());
$this->redirect("accountpayable/show",array('id'=>$id));
}
$this->error('删除失败');
}
}

?>