<?php

class CustomFieldsModel extends Model 
{
protected $tableName = 'customfields';
protected $_auto = array (
array ('cTime','time',Model::MODEL_INSERT,'function'),
);
}?>