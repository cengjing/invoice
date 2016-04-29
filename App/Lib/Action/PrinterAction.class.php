<?php

class PrinterAction extends GlobalAction 
{
public function index() 
{
$this->setTitle('快递单模板');
$A = $this->prefix .'picklist_details A';
$B = $this->prefix .'print_templ B';
$SQL = "SELECT A.id,A.title,B.modify_time 
				FROM $A LEFT JOIN $B ON (A.id=B.express_id) 
				WHERE A.pick_id=4 ORDER BY A.seq ASC";
$vo = M()->query($SQL);
$this->assign ( 'list',$vo );
$this->display ();
}
public function edit() 
{
$model = M ( 'PrintTempl');
$id = $_REQUEST ['id'];
$this->assign('express_id',$id);
$vo = M('PicklistDetails')->where ( array('id'=>$id) )->find();
$this->setTitle($vo ['title'] .'-修改模板');
$vo = $model->where(array('express_id'=>$id))->find();
$this->assign ( 'vo',$vo );
$map ['module'] = 'Printer';
$map ['recordId'] = $vo['id'];
$vo = M ( 'Attach')->where ( $map )->find ();
if ($vo != null) {
$tmp = explode('.',$vo ['savepath']);
$bgImage = __ROOT__ .$tmp[1] .$vo ['savename'];
$this->assign ( 'bgImage',$bgImage );
}
$this->display ();
}
public function update() 
{
$model = D ( 'PrintTempl');
if (false === $model->create ()) {
$this->ajaxReturn ( '',$model->getError (),0 );
}
if(!empty($_REQUEST['id'])){
$result = $model->save ();
$id = $_REQUEST['id'];
}else{
$id = $model->add();
$result = true;
}
if (false !== $result) {
if ($_REQUEST ['attach_id'] != null) {
$attachModel = M ( 'Attach');
$map ['recordId'] = $id;
$map ['module'] = 'Printer';
$attachModel->where($map)->delete();
$map ['id'] = $_REQUEST ['attach_id'];
$attachModel->save ( $map );
}
$re ['id'] = $_REQUEST ['express_id'];
$this->ajaxReturn ( $re,'',1 );
}else {
$this->ajaxReturn ( '','编辑失败!',0 );
}
}
public function upload() 
{
$info = parent::_upload ( 'Printer','','./Public/images/express/');
$info = $info [0];
if(count($info)>1)
{
$tmp = explode('.',$info ['savepath']);
$savePath = 'url('.__ROOT__ .$tmp[1] .$info ['savename'] .')';
$attach_id = $info ['attach_id'];
echo '<script language="javascript" type="text/javascript">window.top.window.stopUpload('.$attach_id .',"'.$savePath .'");</script>';
}else{
echo '<script language="javascript" type="text/javascript">alert('.$info.');</script>';
}
}
}
?>