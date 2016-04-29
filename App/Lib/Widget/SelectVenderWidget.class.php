<?php

class SelectVenderWidget extends Widget 
{
public function render($data) 
{
$map['module'] = 'Vender';
$map['type'] = 1;
$map['use_type'] = 1;
$vo = M('Fields')->where($map)->order('seq asc')->select();
$data ['field'] = $vo;
import ( 'ORG.Util.String');
$data['id'] = String::randNumber(10000000,99999999);
$content = $this->renderFile ( "SelectVender",$data );
return $content;
}
}?>