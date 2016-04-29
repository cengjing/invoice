<?php

class ReportsettingAction extends GlobalAction
{
public function index()
{
$vo = M('Modules')->where(array('report'=>1))->order('seq ASC')->select();
$this->assign('modules',$vo);
$this->setTitle('自定义报表字段');
$this->display();
}
public function getDetails()
{
if(!$this->isAjax())return;
$module_id 	= intval($_REQUEST['module_id']);
$this->assign('module_id',$module_id);
$vo = M('Modules')->where(array('id'=>$module_id))->find();
$moduleName = $vo['module'];
$detailsModuleName = $vo['detailsTable'];
$map['module'] = $moduleName;
$map['level'] = 2;
$map['def_print'] = 1;
$vo = M('Fields')->field('id,title,print')->where($map)->order('seq ASC')->select();
$this->assign('reportHead',$vo);
$map['module'] = $detailsModuleName;
$vo = M('Fields')->field('id,title,print')->where($map)->order('seq ASC')->select();
$this->assign('reportDetails',$vo);
$map = array();
$map['module_id']	= $module_id;
$map['status']		= 1;
$vo = D('CustomFields')->where($map)->order('seq ASC')->select();
$this->assign('reportDefineHead',$vo);
$this->display('details');
}
public function saveDetails()
{
if(!$this->isAjax())return;
$module_id 	= intval($_REQUEST['module_id']);
$vo = M('Modules')->where(array('id'=>$module_id))->find();
$moduleName = $vo['module'];
$detailsModuleName = $vo['detailsTable'];
$model = M('Fields');
$data = array();
$map = array();
$map['module'] 	= $moduleName;
$data['print']	= 0;
$model->where($map)->save($data);
$map['module'] 	= $detailsModuleName;
$model->where($map)->save($data);
$fields = $_REQUEST['fields'];
foreach ($fields as $v)
{
$data = array();
$data['id'] 		= $v;
$data['print'] 		= 1;
$model->save($data);
}
$model = D('CustomFields');
$model->where(array('module_id'=>$module_id))->save(array('print'=>0));
$fields = $_REQUEST['def'];
foreach ($fields as $v)
{
$data = array();
$data['id'] 		= $v;
$data['print'] 		= 1;
$model->save($data);
}
$this->ajaxReturn('','修改成功',1);
}
}
?>