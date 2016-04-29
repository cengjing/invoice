<?php

class CommentWidget extends Widget 
{
public function render($data)
{
$map['module'] = $data['module'];
$map['recordId'] = $data['recordId'];
$uid = session(C('USER_AUTH_KEY'));
$data['uid'] = $uid;
$data['sex'] = getUserInfoById($uid,'sex');
$content = $this->renderFile ( "Comment",$data );
return $content;
}
}?>