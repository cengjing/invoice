function calSum(ele){
	var total = 0;
	var amount,qty;
	if(ele)	ele.set('value', formatNumber(ele.get('value')));
	$$('.bDiv tbody tr').each(function(el, index){
		amount = el.getElement('input[col=amount]').get('value').toFloat();
		qty = el.getElement('input[col=qty]').get('value').toFloat();
		if(amount == '')amount = 0;
		total += (qty*amount).toFloat();
	});
	$('total').set('value', formatNumber(total));
}