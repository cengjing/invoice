<?php

class ExportWidget extends Widget 
{
public function render($data)
{
$map['module'] 	= $data['module'];
$vo = M('Grid')->where($map)->order('seq ASC')->select();
$data['th'] = $vo;
$content = $this->renderFile('index',$data);
return $content;
}
}?>