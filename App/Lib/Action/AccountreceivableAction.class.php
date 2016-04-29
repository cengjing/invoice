<?php

class AccountReceivableAction extends WorkflowAction 
{
public function index()
{
$ret = $this->getFilter('AccountReceivable','A.');
$A = $this->prefix.'account_receivable A ';
$B = $this->prefix.'contacts B';
$C = $this->prefix.'accounts C';
$D = $this->prefix.'user D';
$from 		= " FROM $A 
						LEFT JOIN $B ON A.contact_id=B.id
						LEFT JOIN $C ON B.company_id=C.id
						LEFT JOIN $D ON A.uid=D.id";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.name,C.company,D.name username $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->setTitle('应收单列表');
$this->display();
}
public function add()
{
$this->setTitle('新建应收款单');
$this->display();
}
public function show()
{
$id = intval($_REQUEST['id']);
$vo = D('AccountReceivable')->getInfo($id);
$this->setTitle('应收款单 - '.$vo['base']['id']);
$this->assign ( 'vo',$vo['base'] );
$this->assign( 'contact_id',$vo['contact']['id'] );
$this->display();
}
public function edit()
{
$id = intval($_REQUEST['id']);
$vo = D('AccountReceivable')->getInfo($id);
if(!$vo)
{
$this->error('您查看的单据不存在');
}
if($vo['base']['status'] != 1)
{
$this->error('您查看的单据不能再修改。');
}
$this->setTitle('修改应收款单 - '.$vo['base']['id']);
$this->assign ( 'vo',$vo['base'] );
$this->display();
}
public function insert()
{
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
$_POST['loan'] = ($type ==1 ||$type ==2 ||$type ==5 )?1:-1;
$id = $this->save('AccountReceivable');
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
parent::delete();
}
}

?>