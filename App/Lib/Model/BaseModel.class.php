<?php

class BaseModel extends Model 
{
protected $permModel = '';
protected $_status = array (
'1'=>'已保存',
'2'=>'等待审核',
'3'=>'已审核',
'4'=>'退回审核',
'5'=>'完成',
'6'=>'删除',
);
public function getList($separator = '├-',$blank=true)
{
$vo = $this->order ( 'seq ASC')->select();
$tree = list_to_tree ( $vo,'id','pid','_child');
$list = array ();
tree_to_array ( $tree,0,$list,$separator ,$blank);
return $list;
}
public function getPerm($id,$owner=null)
{
$owner = $owner == null ?session(C('USER_AUTH_KEY')) : $owner;
if (method_exists ( $this,'getInfo'))
{
$vo = $this->getInfo($id);
}
else 
{
$vo = $this->where("id=$id")->find();
$vo['owner'][] = $vo['uid'];
$vo['owner'][] = $vo['assignto'];
}
if($vo == null)
{
return null;
}
if( empty($this->permModel) ) $this->permModel = $this->getModelName();
$group = D('GroupUser')->getUsers( $this->permModel );
if($group['all'])
{
return $vo;
}
else 
{
foreach ($group['users'] as $v)
{
foreach ($vo['owner'] as $u)
{
if( $v['id'] == $u )
{
return $vo;
break;
}
}
}
}
return false;
}
public function saveInfo($module = '')
{
$model = ($module == '') ?$this : D($module);
$model->create ();
$id = intval($_POST['id']);
if($id >0)	
{
$model->save ();
}
else 
{
$id = $model->add ();
$id = $model->saveId($id);
}
return $id;
}
public function saveId($id)
{
if(intval($id) <100000000)
{
$new_id = intval($id) +100000000;
$this->execute("UPDATE __TABLE__ SET id=$new_id WHERE id=$id");
$id = $new_id;
}
return $id;
}
public function getType($id = null)
{
if($id == null)
{
foreach ($this->_type as $k=>$v)
{
$ret[] = array(
'id'=>$k,
'title'=>$v
);
}
return $ret;
}
else 
{
return $this->_type[$id];
}
}
public function getStatus($id = null)
{
if($id == null)
{
foreach ($this->_status as $k=>$v)
{
$ret[] = array(
'id'=>$k,
'title'=>$v
);
}
return $ret;
}
else 
{
return $this->_status[$id];
}
}
}?>