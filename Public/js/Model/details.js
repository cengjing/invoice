window.addEvent('domready', function(){
	if($('submit')){
		$('submit').addEvent('click', function(){
			var el = $('content');
			var val;
			el.getElements('input[name=group]').each(function(el,index){
				if(el.get('checked')){
					val = el.get('value');
				}
			});
			var module = el.getElement('input[name=module]').get('value');
			var action;
			if(module == 'Sod'){
				action = 'sodStat';
			}else if(module == 'Pod'){
				action = 'podStat';
			}
			if(val==1){
				$('chart').empty();
				new Request.HTML({
					url: APP+'/details/'+action,
					data: el,
					update: $('chart_div'),
					onRequest: function(){
						el.spin();
					},
					onSuccess: function(){
						el.unspin();
					}
				}).send();
			}else{
				$('chart_div').empty();
				var url = APP+'/details/'+action+'?' + el.toQueryString();
				new IFrame('chart', {src: url});
			}
		});
	}
});