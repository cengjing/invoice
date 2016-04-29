<?php

class MailModel extends BaseModel 
{
protected $_validate = array (
array ('subject','require','请填写邮件主题'),
array ('content','require','请填写邮件正文'),
);
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
public function getInfo($id)
{
$vo = $this->where(array('id'=>$id))->find();
$ret['owner'][] = $vo['uid'];
$ret['base'] = $vo;
$vo = M('MailDetails')->where(array('mail_id'=>$id))->select();
$ret['details'] = $vo;
return $ret;
}
}?>