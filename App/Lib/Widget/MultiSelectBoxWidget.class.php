<?php

class MultiSelectBoxWidget extends Widget 
{
public function render($data)
{
if(isset($data['pick_id'] )){
$pick_id = $data['pick_id'];
$vo = M('PicklistDetails')->where("pick_id=$pick_id AND status=1")->order('seq ASC')->select();
if(!isset($data['value'])){
foreach ($vo as $v){
if($v['def'] == 1){
$data['value'] = $v['id'];
break;
}
}
}
$data['list'] = $vo;
}else{
$tmp = explode('|',$data['filter']);
if($tmp[0] == 'func'){
$vo = D($data['module'])->$tmp[1]($data['value']);
}
if($tmp[0] == 'where'){
$vo = M($data['module'])->where($tmp[1])->select();
foreach ($vo as &$v)
$v['level'] = 0;
}
$data['list'] = $vo;
}
import ( 'ORG.Util.String');
$data['rand'] = String::randNumber(10000000,99999999);
$content = $this->renderFile ( "index",$data );
return $content;
}
}?>