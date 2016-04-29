window.addEvent('domready', function(){
	if($('submit')){
		$('submit').addEvent('click', function(){
			var el = $('content');
			new Request({
				url: APP+'/user/insert',
				data: el,
				onRequest: function(){
					el.spin();
				},
				onSuccess: function(txt){
					el.unspin();
					results = JSON.decode(txt);
					if(results.status == 1){
						noti.show({ 
						    title: '提示', 
						    message: '保存成功' 
						}); 
						window.location = APP+"/user" ;
					}else{
						noti.show({ 
						    title: '提示', 
						    message: results.info
						}); 
					}
				}
			}).send();
		});
	}
});