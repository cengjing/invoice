<?php

class StepProcessWidget extends Widget 
{
public function render($data)
{
$content = $this->renderFile ( 'index',$data );
return $content;
}
}?>