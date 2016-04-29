<?php
	if (!defined('THINK_PATH')) exit();
	if(file_exists('./Public/db_config.php'))
	$db_config	=	require './Public/db_config.php';
	$array=array(
		'DIRS_CHECK'	=> array(
			//该系统需要检测的文件夹权限
			'/Public/',  		//公共目录
			'/App/Runtime/',  //前台编译缓存目录 
		),
	);
	if(is_array($db_config))
		$config = array_merge($array,$db_config);
	else
		$config = $array;
	
	return $config;
?>