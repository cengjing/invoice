function calSum(el){
	var tr = $(el).getParent('tr');
	var input = tr.getElement('input[col=return_qty]');
	if(input.get('value').toFloat() > input.getProperty('max').toFloat()){
		input.set('value', input.getProperty('max'));
		noti.show({ 
		    title: '提示', 
		    message: '退货数量不能大于订单数量！'
		}); 
		return;
	}
	var return_sum = 0;
	$('return_list').getElements('tr').each(function(el, index){
		qty = el.getElement('input[col=return_qty]').get('value');
		if(qty == ''){ qty = 0}else{ qty.toFloat(); }
		if(qty > 0){
			sale = el.getElement('td[col=sale_price]').get('html');
			if(sale == ''){ sale = 0}else{ sale.toFloat(); }
			other = el.getElement('td[col=other_price]').get('html');
			if(other == ''){ other = 0}else{ other.toFloat(); }
			return_sum += (qty*(sale + other)).toFloat();
		}
	});
	$('sum').set('value',return_sum);
}