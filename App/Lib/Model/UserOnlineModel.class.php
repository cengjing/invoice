<?php

class UserOnlineModel extends Model 
{
protected $tableName = 'user_online';
function isOnline($uid)
{
return;
$info = $this->where('uid='.$uid.' AND activeTime > '.(time()-15*60))->find();
if($info['uid']){
return true;
}else{
return false;
}
}
function refreshOnline()
{
return;
$data["uid"] = session(C('USER_AUTH_KEY'));
$data["activeTime"] = time()-5;
$this->save($data);
}
}?>