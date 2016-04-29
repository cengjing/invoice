<?php

class TabsWidget extends Widget 
{
public function render($data)
{
foreach ($data['tabs'] as &$v){
if(!isset($v['showControl'])){
$v['show'] = true;
}else{
$v['show'] = empty($v['show'])?false:true;
}
}
$module = $data['module'];
$uid = session(C('USER_AUTH_KEY'));
$content = $this->renderFile ('index',$data );
return $content;
}
}