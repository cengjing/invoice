<?php

class EditButtonWidget extends Widget 
{
public function render($data)
{
if(!session('?administrator'))return false;
$content = $this->renderFile ( "index",$data );
return $content;
}
}?>