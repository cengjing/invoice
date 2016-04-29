<?php

class PicklistDetailsModel extends Model {
protected $_validate = array (
array ('title','require','请填写名称'),
);
protected $_auto = array (
array ('status',1),
);
}?>