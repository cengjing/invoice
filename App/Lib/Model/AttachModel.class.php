<?php

class AttachModel extends Model 
{
public function getAttachmentById($id) {
$aId = array ();
$ids = explode ( ',',$id );
foreach ( $ids as $v ) {
array_push ( $aId,$v );
}
$map ['id'] = array ('IN',$aId );
$vo = $this->where ( $map )->select();
return $vo;
}
}?>