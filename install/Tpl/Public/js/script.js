function startInstall()
{
	var DB_HOST = document.install.DB_HOST.value;
	var DB_NAME = document.install.DB_NAME.value;
	var DB_USER = document.install.DB_USER.value;
	var DB_PWD = document.install.DB_PWD.value;
	var DB_PORT = document.install.DB_PORT.value;
	var DB_PREFIX = document.install.DB_PREFIX.value;
	var ADMIN_USERNAME = document.install.ADMIN_USERNAME.value;
	var ADMIN_NAME = document.install.ADMIN_NAME.value;
	var ADMIN_PWD = document.install.ADMIN_PWD.value;
	
	//开始验证
	if(DB_HOST=="")
	{
		alert("请填写数据库主机名或IP地址");
		document.install.DB_HOST.focus();
		return;
	}
	if(DB_NAME=="")
	{
		alert("请填写数据库名");
		document.install.DB_NAME.focus();
		return;
	}
	if(DB_USER=="")
	{
		alert("请填写数据库用户名");
		document.install.DB_USER.focus();
		return;
	}
	if(ADMIN_USERNAME=="")
	{
		alert("请填写管理员登录名");
		document.install.ADMIN_USERNAME.focus();
		return;
	}
	if(ADMIN_NAME=="")
	{
		alert("请填写管理员名称");
		document.install.ADMIN_NAME.focus();
		return;
	}
	if(ADMIN_PWD=="")
	{
		alert("请填写管理员密码");
		document.install.ADMIN_PWD.focus();
		return;
	}
	$("#ajax_loading").ajaxStart(function(){
		$("#tip").html("正在安装......请稍候");
		$(this).fadeIn();
		$("#install").find("input").attr("disabled",true);
	 }); 
	 $("#ajax_loading").ajaxStop(function(){
			$("#ajax_loading").fadeOut();
			$("#install").find("input").attr("disabled",false);
	 }); 
	 
	 
	$.ajax({
		  url: APP+"/Index/install?DB_HOST="+DB_HOST+"&DB_NAME="+DB_NAME+"&DB_USER="+DB_USER+"&DB_PWD="+DB_PWD+"&DB_PORT="+DB_PORT+"&DB_PREFIX="+DB_PREFIX+"&ADMIN_USERNAME="+ADMIN_USERNAME+"&ADMIN_NAME="+ADMIN_NAME+"&ADMIN_PWD="+ADMIN_PWD,
		  cache: false,
		  success:function(data)
		  {
				data = $.evalJSON(data); 

				if(data.status)
				{
					$("#tip").html("安装完成 ！");
					location.href = ROOT_PATH;
				}
				else
				{
					$("#ajax_loading").hide();
					alert(data.info);
				}
		  }
		}); 	
}
