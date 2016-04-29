<?php

class PaymentAction extends WorkflowAction 
{
public function index()
{
$this->setTitle('付款单列表 ');
$ret = $this->getFilter('Payment','A.');
$A = $this->prefix .'payment A ';
$B = $this->prefix .'user B ';
$D = $this->prefix .'vender D ';
$E = $this->prefix .'contacts E ';
$F = $this->prefix .'accounts F ';
$G = $this->prefix .'user G ';
$from 		= " FROM $A 						
						LEFT JOIN $B ON A.uid=B.id
						LEFT JOIN $D ON A.vender_id=D.id
						LEFT JOIN $E ON A.contact_id=E.id
						LEFT JOIN $F ON E.company_id=F.id
						LEFT JOIN $G ON A.assignto=G.id";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.name,D.company vender,E.name contact,F.company,G.name assignto $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->display();
}
public function add()
{
$this->setTitle('新建付款单');
$this->display();
}
public function insert()
{
$assignto= intval($_REQUEST['assignto']);
$contact_id = intval($_REQUEST['contact_id']);
$vender_id = intval($_REQUEST['vender_id']);
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
$this->ajaxReturn('','请填写金额',0);
if(!isset($_REQUEST['id']))
{
$_POST['uid'] = $this->uid;
}
$date = explode('-',$_REQUEST['cTime']);
$_POST['cTime'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
$id = $this->save('Payment');
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
public function show()
{
$id = intval($_REQUEST['id']);
$vo = D('Payment')->getInfo($id);
$this->setTitle('付款单 - '.$vo['base']['id']);
$this->assign('vo',$vo['base']);
$this->assign ( 'vender_id',$vo['vender']['id'] );
$this->display();
}
public function edit()
{
$id = intval($_REQUEST['id']);
$vo = D('Payment')->getInfo($id);
$this->assign('vo',$vo['base']);
$this->setTitle('修改付款单_'.$vo['base']['id']);
$this->display ();
}
}
?>