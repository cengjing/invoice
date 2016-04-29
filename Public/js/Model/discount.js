window.addEvent('domready', function(){
	if($('submit')){
		$('submit').addEvent('click',function(){
			var form = $('content');
			new Request({
				url: APP+"/discount/insert",
				data: form,
				userSpinner: true,
				onRequest: function(){
					form.spin();
				},
				onSuccess: function(txt){
					form.unspin();
					results = JSON.decode(txt);
					if(results.status == 1){
						window.location= APP+"/discount/edit/id/" + results.data.id;
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