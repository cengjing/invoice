<?php

class DepartmentModel extends BaseModel  
{
public function getList()
{
$vo = $this->where(array('status'=>1))->order ('seq ASC')->select();
$tree = list_to_tree ($vo,'id','pid','_child');
$list = array ();
tree_to_array ($tree,0,$list,'','');
return $list;
}
public function getAllChildList($id)
{
$list = $this->getList();
return get_mychild($list,$id);
}
public function getMultiList($val)
{
$vo = $this->order ('seq ASC')->select();
$tree = list_to_tree ($vo,'id','pid','_child');
$list = array ();
tree_to_array ($tree,0,$list,'','');
$t = explode(',',$val);
foreach ($list as &$v){
foreach ($t as $l){
if ($l!=''&&$l == $v['id']){
$v['_checked'] = true;
break;
}
}
}
return $list;
}
}?>