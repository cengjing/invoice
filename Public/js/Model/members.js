window.addEvent('domready', function(){
	if($('sendEmail')){
		$('sendEmail').addEvent('click', function(e){
			e.stop();
			new Request({
				url: APP+'/members/sendEmail',
				onRequest: function(){
					noti.show({ 
					    title: '提示', 
					    message: '正在发送邮件，请稍等...' 
					});
				},
				onSuccess: function(txt){
					results = JSON.decode(txt);
					noti.show({ 
					    title: '提示', 
					    message: results.info 
					}); 
				}
			}).send();
		});
	}
	if($$('.offline')){
		$$('.offline').fade(0.5);
	}
});