<?php

class FlowAction extends GlobalAction 
{
public function getContent()
{
$this->assign('module',$_REQUEST['type']);
$this->assign('id',$_REQUEST['id']);
$this->display('getContent');
}
}
?>