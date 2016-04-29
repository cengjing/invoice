<?php

class VenderModel extends BaseModel 
{
protected $_validate = array (
array ('company','require','请填写供货商名称！'),
array ('contact','require','请填写联系人！'),
);
}?>