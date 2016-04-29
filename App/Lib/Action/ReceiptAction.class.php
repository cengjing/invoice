<?php

class ReceiptAction extends WorkflowAction
{
public function index()
{
$this->setTitle('收款单列表 ');
$ret = $this->getFilter('Receipt','A.');
$A = $this->prefix .'receipt A ';
$B = $this->prefix .'user B ';
$D = $this->prefix .'accounts D ';
$E = $this->prefix .'contacts E ';
$from 		= " FROM $A 						
						LEFT JOIN $B ON A.uid=B.id
						LEFT JOIN $D ON A.company_id=D.id
						LEFT JOIN $E ON A.contact_id=E.id";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,B.name,D.company,E.name contact $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->display();
}
public function add()
{
$this->setTitle('新建收款单');
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
$date = explode('-',$_REQUEST['cTime']);
$_POST['cTime'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
$id = $this->save('Receipt');
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
$vo = D('Receipt')->getInfo($id);
$this->setTitle('收款单 - '.$vo['base']['id']);
$this->assign('vo',$vo['base']);
$this->assign( 'contact_id',$vo['contact']['id'] );
$this->display();
}
public function edit()
{
$id = intval($_REQUEST['id']);
$vo = D('Receipt')->getInfo($id);
if(!$vo)
{
$this->error('您查看的单据不存在');
}
if($vo['base']['status'] != 1)
{
$this->error('您查看的单据不能再修改。');
}
$this->assign('vo',$vo['base']);
$this->setTitle('修改收款单_'.$vo['base']['id']);
$this->display ();
}
public function afterApprove($success)
{
if(!$success)return;
$id = intval($_REQUEST['id']);
$model = D('Receipt');
$vo = $model->where("id=$id")->find();
$type = intval($vo['category_id']);
if($type == 1 ||$type == 2)
{
$data['status'] = 5;
$data['id']		= $vo['id'];
$model->save($data);
if(true)
{
D('AccountReceivable')->autoCreate($vo,$type+2);
}
}
}
public function check()
{
$this->display();
}
Public function delete()
{
parent::delete();
}
}
?>