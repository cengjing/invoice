<?php

class ReportWidget extends Widget 
{
public function render($data)
{
$module = strtolower($data['module']);
$id		= $data['id'];
$map['module'] = $module;
$vo = M('Modules')->where($map)->find();
if (!$vo) 
{
return ;
}
$data['m'] = $vo;
$content = $this->renderFile("index",$data );
return $content;
}
}?>