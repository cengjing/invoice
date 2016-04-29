<?php

class ModulesModel extends Model 
{
public function getItems()
{
return $this->order('seq ASC')->select();
}
}?>