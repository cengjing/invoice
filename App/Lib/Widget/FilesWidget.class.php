<?php

class FilesWidget extends Widget 
{
public function render($data)
{
if(getConfigValue('allow_upload_file') == 1)
{
if(isset($data['module']) &&isset($data['recordId'])){
$map['module'] = $data['module'];
$map['recordId'] = $data['recordId'];
$vo = M('Attach')->where($map)->select();
$data['files'] = $vo;
}else{
import ( 'ORG.Util.String');
$data['module'] = String::randString(20,3);
$data['recordId'] = String::randNumber(10000000,99999999);
}
if(!isset($data['upload']))$data['upload']=true;
if(!isset($data['delete']))$data['delete']=true;
$data['uid'] = session(C('USER_AUTH_KEY'));
$data['allow_upload_file'] = true;
}else{
$data['allow_upload_file'] = false;
}
$content = $this->renderFile ( "index",$data );
return $content;
}
}?>