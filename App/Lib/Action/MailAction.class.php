<?php

class MailAction extends WorkflowAction
{
public function index()
{
$this->setTitle('已发邮件');
$this->_index();
}
public function add()
{
$this->setTitle('新建邮件');
$emailSetting = "<a href='".u('members/show',array('id'=>$this->uid))."'>设置邮箱</a>";
$vo = M('UserEmail')->where(array('uid'=>$this->uid))->find();
if($vo === null)
{
$this->assign('emailError','发件人的信息不正确，邮件将不能正常发送。'.$emailSetting);
}else{
$smtp_host 			= $vo['smtp_host'];
$smtp_email			= $vo['smtp_email'];
$smtp_port			= $vo['smtp_port'];
$smtp_name			= $vo['smtp_name'];
$smtp_password		= $vo['smtp_password'];
if($smtp_host == ''||$smtp_email == ''||$smtp_port == ''||$smtp_name == ''||$smtp_password == '')
{
$this->assign('emailError','邮箱设置不完整，邮件将不能正常发送。'.$emailSetting);
}
}
$this->display();
}
public function contactsFilter()
{
if(!$this->isAjax())return;
$ret = $this->getFilter('Contacts','A.');
$perm = D('GroupUser')->getUsers('Contacts');
$uid = '';
if ($perm['all'] !== true){
foreach ($perm['users'] as $v){
$uid .= ($uid==''?'':',').$v['id'];
}
}
$A = $this->prefix.'contacts A ';
$B = $this->prefix.'accounts B ';
$from 		= " FROM $A LEFT JOIN $B ON A.company_id=B.id ";
if(!empty($uid))
{
$where 		= " WHERE B.assignto IN ($uid) ";
}
if(!empty($ret['where']))
{
$where  	.= (empty($where)?' WHERE ':' AND ').$ret['where'];
}
$where  	.= (empty($where)?' WHERE ':' AND ')."A.email LIKE '%@%'";
$countSQL 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSQL 	= " SELECT A.id,A.status,A.sex,A.blacklist,A.name,A.company_id,B.company,A.email
		$from $where GROUP BY A.cTime DESC";
$vo = M()->query($pageSQL);
$this->assign('list',$vo);
$this->display('filter');
}
public function send()
{
$subject = $_POST['subject'];
$content = safeHtml($_POST['content']);
$ids = array();
foreach ($_REQUEST['item'] as $v)
{
if($v)
{
$ids[] = $v;
}
}
$map['id'] = array('IN',$ids);
$vo = M('Contacts')->field('email')->where($map)->select();
foreach ($vo as $v)
{
$address[] = $v['email'];
}
$model = D('UserEmail');
$ret = $model->sendEmail($this->uid,$subject,$content,$address);
$mailModel = D('Mail');
$_POST['uid'] = $this->uid;
$_POST['status'] = ($ret == false)?0:1;
$mailModel->create();
$id = $mailModel->add();
$mailDetailsModel = M('MailDetails');
foreach ($ids as $v)
{
$data = array();
$data['mail_id'] = $id;
$data['contact_id'] = $v;
$mailDetailsModel->add($data);
}
if(!$ret)
{
$this->error('发送邮件错误，错误原因：'.$model->error_info);
}
$this->assign('jumpUrl',U('mail/index'));
$this->success('发送邮件成功');
}
public function show()
{
$id = intval($_REQUEST['id']);
$vo = D('Mail')->getPerm($id);
if($vo == false)
{
$this->error('您没有查看这张单据的权限。');
}
$this->setTitle($vo['base']['subject']);
$this->assign('vo',$vo['base']);
foreach ($vo['details'] as $v)
{
$ids[] = $v['contact_id'];
}
$map['id'] = array('IN',$ids);
$vo = M('Contacts')->where($map)->field('name,email')->select();
$this->assign('details',$vo);
$this->display();
}
}
?>