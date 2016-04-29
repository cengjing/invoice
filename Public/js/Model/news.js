window.addEvent('domready', function(){
	if($('news_insert')){
		$('news_insert').addEvent('click', function(){
			var el = $('content');
			$('cont').set('html',editor.html());
			new Request({
				url: URL+'/insert',
				data: el,
				onRequest: function(){
					el.spin();
				},
				onSuccess: function(txt){
					el.unspin();
					results = JSON.decode(txt);
					if(results.status == 1){
						window.location= URL+"/edit/id/" + results.data.id ;
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
	
	if($('news_update')){
		$('news_update').addEvent('click', function(){
			var el = $('content');
			$('cont').set('html',editor.html());
			new Request({
				url: URL+'/update',
				data: el,
				onRequest: function(){
					el.spin();
				},
				onSuccess: function(txt){
					el.unspin();
					results = JSON.decode(txt);
					if(results.status == 1){
						window.location= URL+"/edit/id/" + results.data.id ;
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
	