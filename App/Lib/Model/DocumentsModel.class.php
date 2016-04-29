<?php

class DocumentsModel extends Model 
{
protected $_validate = array (
array ('title','require','请填写文档标题！'),
);
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
array ('status','1'),
);
}?>