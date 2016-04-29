<?php

class PriceModel extends Model 
{
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
protected $_Hl = array (
'1'=>'上调',
'2'=>'下调',
);
public function getHl()
{
foreach ($this->_Hl as $k=>$v)
{
$ret[] = array(
'id'=>$k,
'title'=>$v
);
}
return $ret;
}
}?>