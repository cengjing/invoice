<?php

class TreeAction extends GlobalAction {
public function index()
{
$vo = M('plmodule')->order('seq ASC')->select();
$this->assign('modules',$vo);
$this->assign('module',$this->getActionName());
$this->display();
}
public function up() {
if(!$this->isAjax())return;
$id = $_REQUEST ['id'];
$model = D ( MODULE_NAME );
$map ['id'] = $id;
$vo = $model->where ( $map )->find ();
if (false == $vo)
return;
if ($vo ['seq'] == 1)
return;
unset ( $map );
$map ['pid'] = $vo ['pid'];
$map ['seq'] = $vo ['seq'] -1;
$vo2 = $model->where ( $map )->find ();
unset ( $map );
$map ['id'] = $id;
$map ['seq'] = $vo ['seq'] -1;
$model->save ( $map );
unset ( $map );
$map ['id'] = $vo2 ['id'];
$map ['seq'] = $vo ['seq'];
$model->save ( $map );
$data ['s'] = 1;
$data ['beforeId'] = $vo2 ['id'];
echo json_encode ( $data );
}
public function down() {
if(!$this->isAjax())return;
$id = $_REQUEST ['id'];
$model = D ( MODULE_NAME );
$map ['id'] = $id;
$vo = $model->where ( $map )->find ();
unset ( $map );
$map ['pid'] = $vo ['pid'];
$map ['seq'] = $vo ['seq'] +1;
$vo2 = $model->where ( $map )->find ();
if (false == $vo2)
return;
unset ( $map );
$map ['id'] = $id;
$map ['seq'] = $vo ['seq'] +1;
$model->save ( $map );
unset ( $map );
$map ['id'] = $vo2 ['id'];
$map ['seq'] = $vo ['seq'];
$model->save ( $map );
$data ['s'] = 1;
$data ['beforeId'] = $vo2 ['id'];
echo json_encode ( $data );
}
public function update() {
if(!$this->isAjax())return;
$map [$_REQUEST ['colname']] = $_REQUEST ['value'];
$map ['id'] = $_REQUEST ['id'];
$model = D ( MODULE_NAME );
$vo = $model->save ( $map );
if (false !== $vo)
echo 1;
}
public function add() 
{
if(!$this->isAjax())return;
$pid = $_REQUEST ['pid'];
$map ['title'] 	= $_REQUEST ['title'];
$map ['pid'] 	= $pid;
$model = D ( MODULE_NAME );
$vo = $model->where ( "pid=$pid")->field ( 'MAX(seq) AS seq')->select();
if (isset ( $vo [0] ['seq'] )) 
{
$map ['seq'] = $vo [0] ['seq'] +1;
}else {
$map ['seq'] = 1;
}
$model->add ( $map );
echo 1;
}
public function remove()
{
if(!$this->isAjax())return;
$id = intval($_REQUEST ['id']);
$model = D ( MODULE_NAME );
$vo = $model->where ( "pid=$id")->select();
if(count($vo)>0){
echo -1;
}else {
$model->where ( "id=$id")->delete ();
echo 1;
}
}
}?>