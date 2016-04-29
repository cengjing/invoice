<?php

class RoleModel extends Model 
{
public function getMultiList($uid=null)
{
$prefix = C('DB_PREFIX');
if($uid)
{
$A = $prefix.'role A';
$B = $prefix.'role_user B';
$vo = $this->query("SELECT A.*,A.name title,B.user_id
					FROM $A LEFT JOIN $B ON (A.id=B.role_id AND B.user_id=$uid)
					WHERE A.status=1 ORDER BY A.seq ASC");
}
else 
{
$A = $prefix.'role A';
$vo = $this->query("SELECT A.*,A.name title
					FROM $A 
					WHERE A.status=1 ORDER BY A.seq ASC");
}
foreach ($vo as &$v)
{
if (isset($v['user_id']) &&$v['user_id'] == $uid)
{
$v['_checked'] = true;
}
}
return $vo;
}
}?>