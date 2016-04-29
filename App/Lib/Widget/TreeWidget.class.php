<?php

class TreeWidget extends Widget {
public function render($data) {
$name = $data ['name'];
if(!isset($data['child']))$data['child']=true;
$vo = M ( $name )->order ( 'seq ASC')->select();
$tree = list_to_tree ( $vo,'id','pid','_child');
$results = array ();
tree_to_array ( $tree,0,$results,'&nbsp;&nbsp;');
$data ['tree'] = $results;
$content = $this->renderFile ( 'Tree',$data );
return $content;
}
}