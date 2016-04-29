<?php

class WorkflowWidget extends Widget 
{
public function render($data) {
$content = $this->renderFile ( 'Workflow',$data );
return $content;
}
}