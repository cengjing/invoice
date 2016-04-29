window.addEvent('domready', function(){
	if($('submit')){
		$('submit').addEvent('click', function(){
			var el = $('content');
			new Request({
				url: APP+'/stockin/insert',
				data: el,
				onRequest: function(){
					el.spin();
				},
				onSuccess: function(txt){
					el.unspin();
					results = JSON.decode(txt);
					if(results.status == 1){
						window.location= APP+"/stockin" ;
					}else{
						noti.show({ 
						    title: '提示', 
						    message: results.info 
						}); 
						window.location= APP+"/stockin/show/id/"+results.data.id ;
					}
				}
			}).send();
		});
	}
});