window.addEvent('domready', function(){
	if($('submit')){
		$('submit').addEvent('click', function(){
			var el = $('content');
			var val;
			var frame = false;
			el.getElements('input[name=group]').each(function(el,index){
				if(el.get('checked')){
					val = el.get('value');
				}
			});
			var module = el.getElement('input[name=module]').get('value');
			var action;
			if(module == 'salesorder'){
				action = 'sodStat';
				frame = (val==2 || val==3)?false:true;
			}else if(module == 'purchase'){
				action = 'podStat';
				frame = (val==2 || val==3)?false:true;
			}else if(module == 'warehouse'){
				action = 'whStat';
			}
			if(!frame){
				$('chart').empty();
				new Request.HTML({
					url: APP+'/static/'+action,
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
				var url = APP+'/static/'+action+'?' + el.toQueryString();
				new IFrame('chart', {src: url});
			}
		});
	}
});