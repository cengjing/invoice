<?php

class UserEmailModel extends BaseModel 
{
public $error_info = null;
public function sendEmail($uid,$subject,$body,$address)
{
$vo = $this->where(array('uid'=>$uid))->find();
if($vo === null)
{
$this->error_info = '发件人的信息不正确';
return false;
}
$smtp_host 			= $vo['smtp_host'];
$smtp_email			= $vo['smtp_email'];
$smtp_port			= $vo['smtp_port'];
$smtp_name			= $vo['smtp_name'];
$smtp_password		= $vo['smtp_password'];
if($smtp_host == ''||$smtp_email == ''||$smtp_port == ''||$smtp_name == ''||$smtp_password == '')
{
$this->error_info = '邮箱设置不完整';
return false;
}
vendor('phpmailer.mail');
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->Host 		= $smtp_host;
$mail->SMTPAuth 	= true;
$mail->SMTPSecure 	= 'ssl';
$mail->Port 		= $smtp_port;
$mail->Username 	= $smtp_email;
$mail->Password 	= $smtp_password;
$mail->From 		= $smtp_email;
$mail->FromName 	= $smtp_name;
$mail->CharSet 		= "UTF-8";
$mail->Encoding 	= "base64";
if(is_array($address)){
foreach($address as $val){
$mail->AddAddress($val);
}
}else{
$mail->AddAddress($address);
}
$mail->AddReplyTo($smtp_email,$smtp_email);
$mail->IsHTML(true);
$mail->Subject 		= $subject;
$mail->Body 		= $body;
$mail->AltBody 		= "text/html";
if(!$mail->Send())
{
$this->error_info = $mail->ErrorInfo;
return false;
}
else
{
return true;
}
}
}?>