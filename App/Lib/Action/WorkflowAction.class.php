<?php

class WorkflowAction extends GlobalAction 
{
protected $step = array(
'1'=>'已保存',
'2'=>'待审核',
'3'=>'已审核',
'4'=>'退回审核',
'5'=>'完成',
'6'=>'删除',
);
public function _before_show()
{
if($this->isAllowApprove())
{
$this->assign('allowApprove',true);
}
}
public function pend()
{
$module = $this->getActionName ();
$id = intval($_REQUEST['id']);
$data['status'] = 2;
$result = M($module)->where("id=$id AND (status=1 OR status=4)")->save($data);
$uid = $_POST['uid'];
if(false !== $result){
D('Record')->insert($id,'提交审核。',false,$this->getActionName());
if(!$this->isAjax()){
$this->redirect("$module/show",array('id'=>$id));
}else {
$this->ajaxReturn('','',1);
}
}
}
public function approve()
{
$this->doApprove(3);
}
public function disapprove()
{
$this->doApprove(4);
}
private function doApprove($status)
{
$content = $_POST ['content'];
$module = $this->getActionName();
$id = intval($_REQUEST['id']);
$data['status'] = $status;
$data['check_time'] = time();
$map['id'] = $id;
$map['status'] = 2;
$result = M($module)->where($map)->save($data);
if(false != $result){
unset($map);
if($status == 3){
D('Record')->insert($id,'通过审核。',true,$module);
}else {
D('Record')->insert($id,'退回审核。原因：<span style="color:red;">'.Input::forTarea($content).'</span>',true,$module);
}
if( method_exists($this,'afterApprove')) {
if($status == 3){
$this->afterApprove(true);
}else {
$this->afterApprove(false);
}
}
$module = strtolower($module);
$this->redirect("$module/show",array('id'=>$id));
}else {
$this->error('这张单据状态已被改变，不能再执行审核操作。');
}
}
private function isAllowApprove()
{
return true;
$map['record_id'] = intval($_REQUEST['id']);
$map['module'] = $this->getActionName ();
$map['status'] = 0;
$map['action'] = 'approve';
$vo = M('WorkflowHandle')->where($map)->find();
if($vo['to_uid'] != $this->uid){
return false;
}else {
return true;
}
}
public function done()
{
$data['status'] = 5;
$id = $_REQUEST['id'];
$map['id'] = $id;
$module = $this->getActionName ();
M($module)->where($map)->save($data);
}
protected function delete()
{
$module = $this->getActionName ();
$id = $_REQUEST['id'];
$data['status'] = 6;
$map['id'] = $id;
$map['_string'] = 'status=1 OR status=4';
$result = M($module)->where($map)->save($data);
if(false !== $result)
{
D('Record')->insert($id,'删除单据。',false,$this->getActionName());
$this->redirect("$module/show",array('id'=>$id));
}
}
public function report()
{
$module = strtolower($this->getActionName());
$id		= intval($_REQUEST['id']);
$map['module'] = $module;
$vo = M('Modules')->where($map)->find();
if (!$vo) 
{
return ;
}
$this->assign('module',$module);
$this->assign('detailsModule',$vo['detailsTable']);
$this->assign('moduleName',$vo['title']);
$this->assign('title',$vo['title'].' - '.$id);
$model = D(ucwords($module));
$vo = $model->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->assign('vo',$vo['base']);
$details = $model->getDetails($id);
$this->assign('details',$details);
$this->assign('site_address',getConfigValue('site_address'));
$this->display('Public:report');
}
private function _report()
{
$module = strtolower($this->getActionName());
$id		= intval($_REQUEST['id']);
$map['module'] = $module;
$vo = M('WorkflowModule')->where($map)->find();
if (!$vo) 
{
return ;
}
error_reporting(E_ALL);
date_default_timezone_set('Asia/Beijing');
vendor('PHPExcel.Excel');
$objPHPExcel = PHPExcel_IOFactory::load("App/Templates/".$vo['print_template']);
$objPHPExcel->getProperties()->setCreator("")
->setLastModifiedBy("")
->setTitle($vo['title'])
->setSubject("Office 2007 XLSX Test Document")
->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
->setKeywords("office 2007 openxml php")
->setCategory("quote");
$objPHPExcel->getActiveSheet()->setTitle($vo['title'].'-'.$id);
$objPHPExcel->getActiveSheet()->setCellValue("A1",$vo['title']);
$objPHPExcel->setActiveSheetIndex(0);
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'HTML');
$objWriter->save('php://output');
}
public function getData()
{
$id = $_REQUEST['id'];
$name = $this->getActionName ();
$model = D($name.'View');
$vo = $model->where("$name.id=$id")->find();
$vo['cTime'] = date('Y-m-d',$vo['cTime']);
$model = D('WorkflowModule');
$foreighKeyVo = $model->where("module='$name'")->field('foreign_key')->find();
$details = D($name.'Details')->where( $foreighKeyVo['foreign_key']."='$id'")->select();
$XMLText = "<report>";
$XMLText .= '<xml>';
foreach ($details as $k=>$value){
$i = $k+1;
$XMLText .= '<row><i>'.$i.'</i>';
$XMLText .= data_to_xml($value);
$XMLText .= '</row>';
}
$XMLText .= '</xml>';
$XMLText .= '<_grparam>';
$XMLText .= data_to_xml($vo);
$XMLText .= '</_grparam>';
$XMLText .= "</report>";
echo $XMLText;
}
protected function save($module)
{
$model = D($module);
if(isset($_POST['id']))
{
$id = $_POST['id'];
$vo = $model->where("id=$id")->find();
if($vo['status'] != 1 &&$vo['status'] != 4)	
$this->ajaxReturn ('','当前状态不正确，不能修改！',0 );
}
if (false === $model->create ()) 
{
$this->ajaxReturn ('',$model->getError (),0 );
}
if(isset($_POST['id']))
{
$model->save ();
}else{
$id = $model->add ();
$id = $model->saveId($id);
}
return $id;
}
}?>