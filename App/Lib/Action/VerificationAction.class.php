<?php

class VerificationAction extends WorkflowAction 
{
public function index()
{
$ret = $this->getFilter('Verification','A.');
$A = $this->prefix.'verification A ';
$C = $this->prefix .'user C ';
$D = $this->prefix .'vender D ';
$E = $this->prefix .'contacts E ';
$F = $this->prefix .'accounts F ';
$G = $this->prefix .'user G ';
$from 		= " FROM $A ";
$where 		= $ret['where'] != ''?" WHERE ".$ret['where']:'';
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.*,C.name,D.company vender,E.name contact,F.company,G.name assignto$from 
						LEFT JOIN $D ON A.vender_id=D.id
						LEFT JOIN $C ON A.uid=C.id
						LEFT JOIN $E ON A.contact_id=E.id
						LEFT JOIN $F ON E.company_id=F.id
						LEFT JOIN $G ON A.assignto=G.id
						$where ORDER BY A.id DESC";
$this->_pageList ( $countSQL,$pageSQL,$ret['arr_sum']);
$this->setTitle('付款核销');
$this->display();
}
public function add()
{
$this->setTitle('新建应付核销单');
$vid = $_REQUEST['vid'];
if($vid){
$vo = M('Vender')->where(array('id'=>$vid))->find();
$data['vender_id'] = $vo['id'];
$data['company'] = $vo['company'];
$this->assign('data',$data);
}
$this->display();
}
public function showDetails()
{
if(!$this->isAjax()) return ;
$vender_id = intval($_REQUEST['vender_id']);
$assignto= intval($_REQUEST['assignto']);
$contact_id = intval($_REQUEST['contact_id']);
if($assignto ==0 &&$contact_id==0 &&$vender_id==0)
$this->ajaxReturn('','请选择至少一个付款对象',0);
$flag = 0;
if($assignto >0)$flag++;
if($contact_id >0)$flag++;
if($vender_id >0)$flag++;
if($flag != 1)
$this->ajaxReturn('','请选择一个付款对象',0);
$this->writeOffDetails($vender_id,$contact_id,$assignto);
$this->display('details');
}
private function writeOffDetails($vender_id,$contact_id=null,$assignto=null)
{
$accountPayable = array();
$payment		= array();
$vo_ar 	= D('AccountPayable')->getDetails($vender_id,$contact_id,$assignto);
$vo_r 	= D('Payment')->getDetails($vender_id,$contact_id,$assignto);
foreach ($vo_ar as $val)
{
$val['type'] = getAPType($val['type']);
$val['document_type'] = 1;
$val['from_url']	= '<a href="'.u('accountpayable/show',array('id'=>$val['id'])).'" target="_blank">'.$val['id'].'</a>';
if($val['loan'] == 1)
{
$val['loan_type'] = 1;
$accountPayable[$val['id']] = $val;
}
else 
{
$val['loan_type'] = 2;
$payment[$val['id']] = $val;
}
}
foreach ($vo_r as $val)
{
$val['type'] = getPaymentCategory($val['category_id']);
$val['loan_type'] = 2;
$val['document_type'] = 2;
$val['from_url']	= '<a href="'.u('payment/show',array('id'=>$val['id'])).'" target="_blank">'.$val['id'].'</a>';
$payment[$val['id']] = $val;
}
foreach ($accountPayable as &$val)
{
$loan_total += $val['amount'];
$val['file_id'] = $val['id'];
$val['id'] = $val['document_type'].'_'.$val['loan_type'].'_'.$val['id'];
}
$sum['sum_loan_total'] = number_format($loan_total,2,".",",");
$sum['sum_loan_total_non_format'] = $loan_total;
$this->assign('accountPayable_sum',$sum);
unset($sum);
foreach ($payment as &$val)
{
$total += $val['amount'];
$val['file_id'] = $val['id'];
$val['id'] = $val['document_type'].'_'.$val['loan_type'].'_'.$val['id'];
}
$sum['sum_loan_total'] = number_format($total,2,".",",");
$sum['sum_loan_total_non_format'] = $loan_total;
$this->assign('payment_sum',$sum);
$this->assign('accountPayable',$accountPayable);
$this->assign('payment',$payment);
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
$this->ajaxReturn('','请选择一个付款对象',0);
$this->validate();
$this->writeOffDetails($vender_id,$contact_id,$assignto);
$ar_model 	= D('AccountPayable');
$r_model 	= D('Payment');
$ar = $this->__get('accountPayable');
$r = $this->__get('payment');
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
$this->ajaxReturn('','请选择应付款明细。',0);
}
if ($sum2<=0 ||count($gp2) == 0)
{
$this->ajaxReturn('','请选择付款明细。',0);
}
$_POST['uid'] = $this->uid;
$_POST['amount'] = $all_write_off;
$_POST['status'] = 5;
$model = M('VerificationDetails');
$model->startTrans();
$id = $this->save('Verification');
if ($id !== false) 
{
$model->where("v_id=$id")->delete();
foreach ($gp1 as $v)
{
$v['v_id'] = $id;
$ret = $model->add($v);
if($ret == false)
{
$model->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
$ret = $ar_model->writeOff($v['document_id'],$v['amount']);
if($ret === false)
{
$model->rollback();
$this->ajaxReturn ('','核销应付款明细'.$v['document_id'].'失败!',0);
}
}
foreach ($gp2 as $v)
{
$v['v_id'] = $id;
$ret = $model->add($v);
if($ret == false)
{
$model->rollback();
$this->ajaxReturn ('','记录明细失败!',0);
}
$ret = $r_model->writeOff($v['document_id'],$v['amount']);
if($ret === false)
{
$model->rollback();
$this->ajaxReturn ('','核销付款明细'.$v['document_id'].'失败!',0);
}
}
$result['id'] = $id;
if($model->commit())
{
$this->ajaxReturn($result,'',1);
}
}
}
public function show()
{
$id = intval($_REQUEST['id']);
$vo = D('Verification')->getInfo($id);
$this->setTitle('应收核销单 - '.$vo['base']['id'].' - '.$vo['base']['abstract']);
$this->assign('vo',$vo['base']);
$vo = M('VerificationDetails')->where("v_id=$id")->select();
$AR = D('AccountPayable');
$R = D('Payment');
foreach ($vo as &$v)
{
if($v['type'] == 1)
{
$tmp = $AR->where("id=".$v['document_id'])->find();
$v['type'] = getAPType($tmp['type']);
$v['loan'] = ($v['loan'] == -1)?'贷':'借';
$v['document_id'] = "<a href='__APP__/accountpayable/show/id/".$v['document_id']."'>".$v['document_id']."</a>";
}
if($v['type'] == 2)
{
$tmp = $R->where("id=".$v['document_id'])->find();
$v['type'] = getPaymentCategory($tmp['category_id']);
$v['loan'] = ($v['loan'] == -1)?'贷':'借';
$v['document_id'] = "<a href='__APP__/payment/show/id/".$v['document_id']."'>".$v['document_id']."</a>";
}
}
$this->assign('details',$vo);
$this->display();
}
}
?>