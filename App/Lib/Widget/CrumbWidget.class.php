<?php

class CrumbWidget extends Widget 
{
public function render($data)
{
$map['module'] 		= $data['module'];
$map['record_id'] 	= $data['record_id'];
$vo = M('EditHistory')->where($map)->select();
$data['list']		= $vo;
$content = $this->renderFile ( "index",$data );
return $content;
}
}?>