<?php

class ReconciliationAction extends WorkflowAction  
{
public function index()
{
$ret = $this->getFilter('Reconciliation','A.');
$A = $this->prefix.'reconciliation A ';
$C = $this->prefix .'user C ';
$D = $this->prefix .'accounts D ';
$E = $this->prefix .'contacts E ';
$from 		= " FROM $A ";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,C.name,D.company,E.name contact $from 
						LEFT JOIN $E ON A.contact_id=E.id
						LEFT JOIN $D ON E.company_id=D.id
						LEFT JOIN $C ON A.uid=C.id
						$where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->setTitle('收款核销');
$this->display();
}
public function add()
{
$this->setTitle('新建应收核销单');
$cid = $_REQUEST['cid'];
if($cid){
$vo = D('Contacts')->getInfo($cid);
$data['contact'] = $vo['base']['name'];
$data['contact_id'] = $vo['base']['id'];
$data['company'] = $vo['account']['company'];
$data['company_id'] = $vo['account']['id'];
$this->assign('data',$data);
}
$this->display();
}
public function showDetails()
{
if(!$this->isAjax()) return ;
$contact_id = intval($_REQUEST['contact_id']);
$this->writeOffDetails($contact_id);
$this->display('details');
}
public function insert()
{
$this->validate();
$contact_id = intval($_REQUEST['contact_id']);
$this->writeOffDetails($contact_id);
$ar_model 	= D('AccountReceivable');
$r_model 	= D('Receipt');
$ar = $this->__get('receivable');
$r = $this->__get('receipt');
$gp1 = array();
$gp2 = array();
$sum1 = 0;
$sum2 = 0;
foreach ($_REQUEST['item'] as $v)
{
$arr = explode("_",$v);
$document_type 		= $arr[0];
$loan_type 			= $arr[1];
$id					= $arr[2];
if($loan_type == 1)
{
$sum1 += $ar[$id]['amount'];
}else 
{
$sum2 += $r[$id]['amount'];
}
}
$all_write_off = 0;
$tmp1 = $sum2;
$tmp2 = $sum1;
foreach ($_REQUEST['item'] as $v)
{
$arr = explode("_",$v);
$document_type 		= $arr[0];
$loan_type 			= $arr[1];
$id					= $arr[2];
if($loan_type == 1)
{
$amount = $ar[$id]['amount'];
if($amount <= $tmp1 &&$tmp1>0)
{
$writeOff = $amount;
$tmp1 -= $amount;
}else
{
$writeOff = $tmp1;
$tmp1 = 0;
}
if($writeOff >0)
{
$data = array();
$data['type'] 			= $document_type;
$data['document_id'] 	= $id;
$data['amount']			= $writeOff;
$data['loan']			= $loan_type;
if($document_type == 1)
{
$gp1[] = $data;
}else 
{
$gp2[] = $data;
}
}
}
else 
{
$amount = $r[$id]['amount'];
if($amount <= $tmp2 &&$tmp2>0)
{
$writeOff = $amount;
$tmp2 -= $amount;
}else
{
$writeOff = $tmp2;
$tmp2 = 0;
}
if($writeOff >0)
{
$data = array();
$data['type'] 			= $document_type;
$data['document_id'] 	= $id;
$data['amount']			= $writeOff;
$data['loan']			= -1;
if($document_type == 1)
{
$gp1[] = $data;
}else 
{
$gp2[] = $data;
}
$all_write_off += $writeOff;
}
}
}
if ($sum1<=0 ||count($gp1) == 0)
{
$this->ajaxReturn('','请选择应收款明细。',0);
}
if ($sum2<=0 ||count($gp2) == 0)
{
$this->ajaxReturn('','请选择收款明细。',0);
}
$_POST['uid'] 		= $this->uid;
$_POST['amount'] 	= $all_write_off;
$_POST['status']	= 5;
$model = M('ReconciliationDetails');
$model->startTrans();
$id = $this->save('Reconciliation');
$record = D('Record');
if ($id !== false) 
{
$model->where("r_id=$id")->delete();
foreach ($gp1 as $v)
{
$v['r_id'] = $id;
$ret = $model->add($v);
if($ret == false)
{
$model->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
$ret = $ar_model->writeOff($v['document_id'],$v['amount'],$id);
$record->insert($v['document_id'],'核销单据：'.$id.'。核销金额：'.$v['amount'],false,'AccountReceivable');
if($ret === false)
{
$model->rollback();
$this->ajaxReturn ('','核销应收款明细'.$v['document_id'].'失败!',0);
}
}
foreach ($gp2 as $v)
{
$v['r_id'] = $id;
$ret = $model->add($v);
if($ret == false)
{
$model->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
$ret = $r_model->writeOff($v['document_id'],$v['amount']);
$record->insert($v['document_id'],'核销单据：'.$id.'。核销金额：'.$v['amount'],false,'Receipt');
if($ret === false)
{
$model->rollback();
$this->ajaxReturn ('','核销收款明细'.$v['document_id'].'失败!',0);
}
}
$result['id'] = $id;
if($model->commit())
{
$record->insert($id,'核销完成',false,'Reconciliation');
$this->ajaxReturn($result,'',1);
}
}
}
public function update()
{
$this->insert();
}
private function writeOffDetails($contact_id)
{
$receivable 	= array();
$receipt 		= array();
$vo_ar 	= D('AccountReceivable')->getDetails($contact_id);
$vo_r 	= D('Receipt')->getDetails($contact_id);
foreach ($vo_ar as $val)
{
$val['type'] = getARType($val['type']);
$val['document_type'] = 1;
$val['from_url']	= '<a href="'.u('accountreceivable/show',array('id'=>$val['id'])).'" target="_blank">'.$val['id'].'</a>';
if($val['loan'] == 1)
{
$val['loan_type'] = 1;
$receivable[$val['id']] = $val;
}
else 
{
$val['loan_type'] = 2;
$receipt[$val['id']] = $val;
}
}
foreach ($vo_r as $val)
{
$val['type'] = getReceiptCategory($val['category_id']);
$val['loan_type'] = 2;
$val['document_type'] = 2;
$val['from_url']	= '<a href="'.u('receipt/show',array('id'=>$val['id'])).'" target="_blank">'.$val['id'].'</a>';
$receipt[$val['id']] = $val;
}
foreach ($receivable as &$val)
{
$loan_total += $val['amount'];
$val['file_id'] = $val['id'];
$val['id'] = $val['document_type'].'_'.$val['loan_type'].'_'.$val['id'];
}
$sum['sum_loan_total'] = number_format($loan_total,2,".",",");
$sum['sum_loan_total_non_format'] = $loan_total;
$this->assign('receivable_sum',$sum);
unset($sum);
foreach ($receipt as &$val)
{
$total += $val['amount'];
$val['file_id'] = $val['id'];
$val['id'] = $val['document_type'].'_'.$val['loan_type'].'_'.$val['id'];
}
$sum['sum_loan_total'] = number_format($total,2,".",",");
$sum['sum_loan_total_non_format'] = $loan_total;
$this->assign('receipt_sum',$sum);
$this->assign('receivable',$receivable);
$this->assign('receipt',$receipt);
}
public function show()
{
$id = intval($_REQUEST['id']);
$vo = D('Reconciliation')->getInfo($id);
$this->setTitle('应收核销单 - '.$vo['base']['id'].' - '.$vo['base']['abstract']);
$this->assign('vo',$vo['base']);
$vo = M('ReconciliationDetails')->where("r_id=$id")->select();
$AR = D('AccountReceivable');
$R = D('Receipt');
foreach ($vo as &$v)
{
if($v['type'] == 1)
{
$tmp = $AR->where("id=".$v['document_id'])->find();
$v['type'] = getARType($tmp['type']);
$v['loan'] = ($v['loan'] == -1)?'贷':'借';
$v['document_id'] = "<a href='__APP__/accountreceivable/show/id/".$v['document_id']."'>".$v['document_id']."</a>";
}
if($v['type'] == 2)
{
$tmp = $R->where("id=".$v['document_id'])->find();
$v['type'] = getReceiptCategory($tmp['category_id']);
$v['loan'] = ($v['loan'] == -1)?'贷':'借';
$v['document_id'] = "<a href='__APP__/receipt/show/id/".$v['document_id']."'>".$v['document_id']."</a>";
}
}
$this->assign('details',$vo);
$this->display();
}
Public function delete()
{
$id = intval($_REQUEST['id']);
$model = D('Reconciliation');
$model->startTrans();
$vo = $model->where(array('id'=>$id))->find();
if($vo['status'] != 5)
{
$this->error('核销单据状态不正确。');
}
$data = array();
$data['status']		 = 6;
$data['id']			 = $id;
$model->save($data);
$AR = D('AccountReceivable');
$R = D('Receipt');
$record = D('Record');
$vo = M('ReconciliationDetails')->where("r_id=$id")->select();
foreach ($vo as $val)
{
$data = array();
$data['id']			 = $val['document_id'];
$data['write_off']	 = 0;
if($val['type'] == 1)
{
$arVo = $AR->where(array('id'=>$val['document_id']))->find();
$amount = $arVo['amount'];
$data['status']		 = 1;
$AR->save($data);
$record->insert($val['document_id'],'删除核销',false,'AccountReceivable');
if($arVo['type'] == 1)
{
$invoiceNo = intval($arVo['from_id']);
if($invoiceNo>0)
{
unset($data);
$data['status'] 	= 2;
$data['id'] 		= $invoiceNo;
M('Invoice')->save($data);
}
}
}
if($val['type'] == 2)
{
$data['status']		 = 3;
$R->save($data);
$record->insert($val['document_id'],'删除核销',false,'Receipt');
}
}
$details = M('SalesorderRecon')->where(array('rid'=>$id))->select();
$SO = D('Salesorder');
foreach ($details as $v)
{
$order_id = $v['order_id'];
if(!empty($order_id))
{
$sql = 'UPDATE __TABLE__ SET collection=collection-'.$v['amount'].' WHERE id='.$order_id;
$SO->execute($sql);
$record->insert($id,'删除收款金额:'.$v['amount'],false,'Salesorder');
$SO->autoCheck($order_id);
}
}
if($model->commit())
{
$record->insert($id,'删除核销',false,'Reconciliation');
$this->success('删除成功');
}
}
}
?>