<?php

class FormDetailsWidget extends Widget 
{
public function render($data)
{
$map['module'] 	= $data['module'];
$map['print']	= 1;
$vo = M('Fields')->where($map)->order('seq ASC')->select();
$data['th'] = $vo;
$content = $this->renderFile('index',$data);
return $content;
}
}?>