<?php

class ReviseAction extends GlobalAction 
{
public function index()
{
return;
}
public function add()
{
return;
}
public function edit()
{
return;
}
public function insert()
{
return;
}
public function load()
{
$module = $_REQUEST['module'];
$this->assign('module',$module);
$vo = M('Modules')->where(array('module'=>$module))->find();
$moduleName = $vo['title'];
$id = intval($_REQUEST['id']);
$title = '管理员修改 - '.$moduleName.' - '.$id;
$this->assign('tabTile',$title);
$this->setTitle($title);
$model = D(ucfirst($module));
if (method_exists($model,'getInfo')){
$vo = $model->getInfo($id);
$vo = $vo['base'];
}else{
$vo = $model->where(array('id'=>$id))->find();
}
$this->assign('vo',$vo);
$this->display();
}
public function update()
{
$module = $_REQUEST['module'];
$id = intval($_REQUEST['id']);
$fields = M('Fields')->where(array('module'=>$module,'level'=>2,'super_edit'=>1))->select();
foreach ($fields as $v)
{
if ($v['type'] == 10){
$_POST[$v['field']] = isset($_POST[$v['field']]) ?1 : 0;
}elseif ($v['type'] == 3){
$_POST[$v['field']] = isset($_POST[$v['field']]) ?time2unix($_POST[$v['field']]) : null;
}elseif ($v['type'] == 2){
$_POST[$v['field']] = str_replace(",",'',$_POST[$v['field']]);
}
}
$editContent = '';
$vo = M($module)->where(array('id'=>$id))->find();
foreach ($fields as $v){
$field = $v['field'];
if(isset($vo[$field])){
if($vo[$field] != $_POST[$field]){
if ($v['type'] == 2 &&floatval($vo[$field]) != floatval($_POST[$field])){
$editContent .= '<p>修改了 ['.$v['title'].'] "'.floatval($vo[$field]).'" 到 "'.floatval($_POST[$field]).'"</p>';
}elseif($v['type'] == 3 &&floatval($vo[$field])==0 &&$_POST[$field]==''){
}else {
$editContent .= '<p>修改了 ['.$v['title'].']</p>';
}
}
}
}
D('Record')->insert($id,'管理员修改'.$editContent,false,$module);
$model = D($module);
if (false === $model->create ()) {
$this->error ( $model->getError () );
}
$result = $model->save ();
if (false !== $result) {
$this->success ( '编辑成功!');
}else {
$this->error ( '编辑失败!');
}
}
}?>