<?php

class BackupAction extends GlobalAction 
{
public function index()
{
$this->setTitle('数据备份');
$dir = './data/database/';
if(is_dir($dir)){
if($dh = opendir($dir)){
while (($filename = readdir($dh)) !== false) {
if($filename != '.'&&$filename != '..'){
if(substr($filename,strrpos($filename,'.')) == '.sql'){
$file = $dir.$filename;
$filemtime = date('Y-m-d H:i:s',filemtime($file));
$addtime[] = $filemtime;
$log[] = array(
'filename'=>$filename,
'filesize'=>byte_format(filesize($file)),
'addtime'=>$filemtime,
'filepath'=>C('SITE_URL').$file,
);
}
}
}
}
}else{
checkDir($dir);
}
array_multisort($addtime,SORT_ASC,$log);
$this->assign('log',$log);
$this->display();
}
public function add()
{
$tables		= array();
$volume		= isset($_GET['volume']) ?(intval($_GET['volume']) +1) : 1;
$filename	= date('ymd').'_'.substr(md5(uniqid(rand())),0,10);
$tables = D('Database')->getTableList();
$tables = getSubByKey($tables,'Name');
$filename	= trim($_REQUEST['filename']) ?trim($_REQUEST['filename']) : $filename;
$startfrom	= intval($_REQUEST['startform']);
$tableid	= intval($_REQUEST['tableid']);
$sizelimit	= intval($_REQUEST['sizelimit']) ?intval($_REQUEST['sizelimit']) : 1000;
$tablenum	= count($tables);
$filesize	= $sizelimit*1000;
$complete	= true;
$tabledump	= '';
if($tablenum == 0)
$this->error('请选择备份的表');
for(;$complete &&($tableid <$tablenum) &&strlen($tabledump)+500 <$filesize;$tableid++){
$sqlDump = D('Database')->getTableSql($tables[$tableid],$startfrom,$filesize,strlen($tabledump),$complete);
$tabledump .= $sqlDump['tabledump'];
$complete	= $sqlDump['complete'];
$startfrom	= intval($sqlDump['startform']);
if($complete)
$startfrom = 0;
}
!$complete &&$tableid--;
if ( trim($tabledump) ) {
$filepath = './data/database/'.$filename."_$volume".'.sql';
$fp = @fopen($filepath,'wb');
if ( !fwrite($fp,$tabledump) ) {
$this->error('文件目录写入失败, 请检查data目录是否可写');
}else {
$url_param = array(
'filename'=>$filename,
'sizelimit'=>$sizelimit,
'tableid'=>$tableid,
'startform'=>$startfrom,
'volume'=>$volume,
);
$url = U('backup/add',$url_param);
$this->assign('jumpUrl',$url);
$this->success("备份第{$volume}卷成功");
}
}else {
$this->assign('jumpUrl',U('backup/index'));
$this->success("备份成功");
}
}
function import(){
$filename = $_GET['filename'];
$sqldump = '';
$file = './data/database/'.$filename.'.sql';
if(file_exists($file)){
$fp = @fopen($file,'rb');
$sqldump = fread($fp,filesize($file));
fclose($fp);
}
$ret = D('Database')->import($sqldump);
if($ret) {
$this->success('导入成功');
}else{
$this->error('导入失败');
}
}
public function delete() {
$file = $_GET['filename'];
$file = './data/database/'.$file.'.sql';
file_exists($file) &&@unlink($file);
$this->success('删除成功');
}
}
?>