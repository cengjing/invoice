function cal_sum(){
	var freight=0,otherFund=0,sum=0,ele,el,val;
	
	el = $('content');
	el.getElements('input[col=of]').each(function(element){
		val = element.get('value');
		if(val == '')val = 0;
		otherFund +=val.toFloat();
	});
	$('other_fund').set('value',formatNumber(otherFund));
	el.getElements('input[col=ft]').each(function(element){
		val = element.get('value');
		if(val == '')val = 0;
		freight +=val.toFloat();
	});
	$('freight').set('value',formatNumber(freight));
	
	sum = $('sum').get('value');
	if(sum == '')sum = 0;
	sum = sum.toFloat();
	
	ele = $('amount');
	if(ele)ele.set('value', formatNumber(sum + otherFund + freight));
}
window.addEvent('domready', function(){
	if($('freight')){
		$('freight').addEvent('change', function(e){cal_sum();});
	}
	if($('other_fund')){
		$('other_fund').addEvent('change', function(e){cal_sum();});
	}
});