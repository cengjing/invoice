<?php

class SelectProductWidget extends Widget 
{
public function render($data) 
{
$map['module'] = 'Products';
$map['type'] = 1;
$map['use_type'] = 1;
$vo = M('Fields')->where($map)->order('seq asc')->select();
$data ['field'] = $vo;
$content = $this->renderFile ( "SelectProduct",$data );
return $content;
}
}?>