<?php
	if(file_exists('./Public/db_config.php'))
		$db_config	=	require './Public/db_config.php';

	$array = array (

	'DB_TYPE' => 'mysql', 
	'URL_MODEL' => 2,
	'URL_CASE_INSENSITIVE' => true,
	
	'TOKEN_ON' => false,
	'TOKEN_NAME' => '__hash__',
	'TOKEN_TYPE' => 'md5',
	
	'DEFAULT_MODULE' => 'Index',
	'TMPL_CACHE_ON' => false,
	'DB_FIELDS_CACHE'=>true,
	'USER_AUTH_ON' => true, 
	'USER_AUTH_TYPE' => 2, 
	'USER_AUTH_KEY' => 'authId', 
	'ADMIN_AUTH_KEY' => 'administrator', 
	'USER_AUTH_MODEL' => 'User', 
	'AUTH_PWD_ENCODER' => 'md5', 
	'USER_AUTH_GATEWAY' => '/public/login', 
	'NOT_AUTH_MODULE' => 'Public,Index,Members,Flow,Common', 
	'REQUIRE_AUTH_MODULE' => '', 
	'NOT_AUTH_ACTION' => '', 
	'REQUIRE_AUTH_ACTION' => '', 
	'GUEST_AUTH_ON' => false, 
	'GUEST_AUTH_ID' => 0, 
	'RBAC_ROLE_TABLE' => $db_config['DB_PREFIX'].'role', 
	'RBAC_USER_TABLE' => $db_config['DB_PREFIX'].'role_user', 
	'RBAC_ACCESS_TABLE' => $db_config['DB_PREFIX'].'access', 
	'RBAC_NODE_TABLE' => $db_config['DB_PREFIX'].'node',
			
	'LOG_RECORD' => true,
	'LOG_EXCEPTION_RECORD'  => 	true,
	'LOG_LEVEL' => 'EMERG,CRIT,ERR,SQL',
	
	'CRYPT_KEY'	=> 'keywords',
	
	'DEFAULT_TIMEZONE' => 'PRC',
	
	'SESSION_PREFIX'	=> 'sibo_',
	//'SESSION_TYPE'		=> 'Db',
	'SESSION_EXPIRE'	=> 3600,
	'SESSION_TABLE'		=> $db_config['DB_PREFIX'].'session', 
	
	'LANG_SWITCH_ON'	=>   true,
	'DEFAULT_LANG'   	=>	'zh-cn',	 // 默认语言
	'LANG_AUTO_DETECT'  =>   true,     // 自动侦测语言
	
			
	'TMPL_ACTION_ERROR'		=> APP_PATH.'Tpl/Public/success.html',
	'TMPL_ACTION_SUCCESS'		=> APP_PATH.'Tpl/Public/success.html',
	'PAGE_ROLLPAGE'		=> 10,
	
	'FREE_VERSION'		=> true,
	);
	if(is_array($db_config))
		$config = array_merge($db_config,$array);
	else
		$config = $array;
	return $config;
?>