window.addEvent('domready', function(){
	if($('allEditBtn')){
		$('allEditBtn').addEvent('click',function(){
			var el = $('content');
			new Request({
				url: APP+'/accounts/updateAll',
				data: el,
				onRequest: function(){
					el.spin();
				},
				onSuccess: function(txt){
					el.unspin();
					results = JSON.decode(txt);
					noti.show({ 
					    title: '提示', 
					    message: results.info 
					});
				}
			}).send();
		});
	}
	if($('transfer')){
		$('transfer').addEvent('click',function(){
			var items = '';
			$$('.grid-item').each(function(el, index){
				if(el.get('checked')){
					items += ((items=='')?'':',') + el.get('value');
				}
			});
			if(items == ''){
				noti.show({ 
				    title: '提示', 
				    message: '请至少选择一个选项' 
				});
				return;
			}
			window.location = APP+'/accounts/editAll/items/'+items;
		});
	}
});