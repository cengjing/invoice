<?php

class EmptyDataWidget extends Widget
{
public function render($data)
{
$content = $this->renderFile ( "index",$data );
return $content;
}
}?>