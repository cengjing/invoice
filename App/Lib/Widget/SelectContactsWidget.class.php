<?php

class SelectContactsWidget extends Widget 
{
public function render($data) 
{
import ( 'ORG.Util.String');
$data['id'] = String::randNumber(10000000,99999999);
$content = $this->renderFile ( "SelectContacts",$data );
return $content;
}
}?>