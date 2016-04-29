<?php

class SelectBoxWidget extends Widget 
{
public function render($data)
{
if(!isset($data['list']))
{
if(isset( $data['pick_id'] ))
{
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
$tree = list_to_tree ( $vo,'id','pid','_child');
$list = array ();
tree_to_array ( $tree,0,$list,'','');
$data['list'] = $list;
}
else
{
$tmp = explode('|',$data['filter']);
if($tmp[0] == 'func'){
$vo = D($data['module'])->$tmp[1]();
}
if($tmp[0] == 'where'){
$vo = M($data['module'])->where($tmp[1])->select();
foreach ($vo as &$v)
$v['level'] = 0;
}
$data['list'] = $vo;
}
}
if(!isset($data['list_width']))
{
$data['list_width'] = 260;
}
if(!isset( $data['allSelect'] )) $data['allSelect'] = false;
import ( 'ORG.Util.String');
$data['rand'] = String::randNumber(10000000,99999999);
$content = $this->renderFile ( "index",$data );
return $content;
}
}?>