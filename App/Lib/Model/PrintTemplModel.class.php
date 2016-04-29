<?php

class PrintTemplModel extends Model 
{
protected $_validate = array (
array ('title','require','请填写单据名称！'),
);
protected $_auto = array (
array ('modify_time','time',Model::MODEL_BOTH,'function'),
);
}?>