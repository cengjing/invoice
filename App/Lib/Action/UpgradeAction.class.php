<?php

class UpgradeAction extends GlobalAction
{
private $pathUrl = 'http://www.siboh.com/download/freecrm/';
private $versionUrl = 'http://www.siboh.com/version/free_crm_version.php';
private function getRealPath()
{
return getcwd();
}
public function index()
{
$this->setTitle('系统升级');
$version = json_decode(file_get_contents($this->versionUrl));
$currentVersion = include $this->getRealPath().'/version.php';
$needUpgrade = false;
$latestVersion = $currentVersion['server_version'];
foreach ($version as $k=>$v)
{
if (floatval($k) >floatval($currentVersion['server_version']))
{
$needUpgrade = true;
$latestVersion = $k;
$latestRelease = $v;
}
}
$this->assign('needUpgrade',$needUpgrade);
$this->assign('latestVersion',$latestVersion);
$this->assign('latestRelease',$latestRelease);
$this->assign('version',$currentVersion);
$this->display();
}
public function execute()
{
$version = json_decode(file_get_contents($this->versionUrl));
$currentPath = $this->getRealPath();
$upgradezip_path = $currentPath.'/caches_upgrade';
if(!file_exists($upgradezip_path)) {
@mkdir($upgradezip_path);
}
$currentVersion = include $currentPath.'/version.php';
foreach ($version as $k=>$v)
{
if (floatval($k) >floatval($currentVersion['server_version']))
{
$updateName = "$v.zip";
$upgradezip_url = $this->pathUrl.$updateName;
$upgradezip_source_path = $upgradezip_path.'/'.$v;
$upgradezip_file = $upgradezip_path.'/'.$updateName;
@file_put_contents($upgradezip_file,@file_get_contents($upgradezip_url));
import ( "ORG.Util.pclzip");
$archive = new PclZip($upgradezip_file);
if($archive->extract(PCLZIP_OPT_PATH,$upgradezip_source_path,PCLZIP_OPT_REPLACE_NEWER) == 0)
{
$this->error('错误：'.$archive->errorInfo(true));
}
import ( "ORG.Io.Dir");
$dir = new Dir();
Dir::copyDir($upgradezip_source_path,$currentPath);
$upgradeSqlFile = $upgradezip_source_path.'/update.sql';
$this->restore($upgradeSqlFile);
$current_version = array('server_version'=>$k,'server_release'=>$v);
@file_put_contents($currentPath.'/version.php','<?php return '.var_export($current_version,true).';?>');
}
}
Dir::delDir($upgradezip_path);
$this->success('系统升级完成');
}
private function restore($file)
{
set_time_limit(0);
$db = M();
$sql = file_get_contents($file);
$sql = $this->remove_comment($sql);
$sql = trim($sql);
$sql = str_replace("\r",'',$sql);
$segmentSql = explode(";\n",$sql);
foreach($segmentSql as $k=>$itemSql)
{
$itemSql = str_replace("%DB_PREFIX%",C('DB_PREFIX'),$itemSql);
if(!empty($itemSql))
$db->query($itemSql);
}
return "";
}
private function remove_comment($sql)
{
$sql = preg_replace('/^\s*(?:--|#).*/m','',$sql);
$sql = preg_replace('/^\s*\/\*.*?\*\//ms','',$sql);
return $sql;
}
}?>