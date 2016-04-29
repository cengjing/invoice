function calSum(el){
	var tr = $(el).getParent('tr');
	var el = tr.getElement('input[col=qty]');
	var qty = el.get('value').toFloat();
	el.set('value', formatNumber(qty));
	var unit = tr.getElement('input[col=unit_price]').get('value').toFloat();
	el = tr.getElement('input[col=sale_price]');
	var price = el.get('value').toFloat();
	el.set('value', formatNumber(price));
	var ds = formatNumber((1 - (price/unit))*100);
	tr.getElement('input[col=discount]').set('value', ds);
	el = tr.getElement('input[col=other_price]');
	var other_price = el.get('value').toFloat();
	el.set('value', formatNumber(other_price));
	other_price = (other_price == '') ? 0 : other_price.toFloat().round(2);
	var sum = qty*(price+other_price);
	tr.getElement('div[col=sum_price]').set('html', formatNumber(sum));
	cal_order_sum();
}
window.addEvent('domready', function(){
	var inventory = $$('.inventory-qty');
	if(inventory) { inventory.each(function(el){show_inventory_qty(el);}); }
	var discount = $$('.discount');
	if(discount) { discount.each(function(el){getDiscount(el);}); }
	if($('submit')){
		$('submit').addEvent('click',function(){
			if($('content').getElements('.bDiv tbody tr').length==0){
				noti.show({ 
				    title: '提示', 
				    message: '请在报价单明细中增加产品。'
				}); 
				return;
			}
			var form = $('form');
			new Request({
				url: form.get('action'),
				data: form,
				userSpinner: true,
				onRequest: function(){
					form.spin();
				},
				onSuccess: function(txt){
					form.unspin();
					var results = JSON.decode(txt);
					if(results.status == 1){
						window.location= APP+"/quotes/show/id/" + results.data.id;
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
	if($('freight')){
		$('freight').addEvent('change', function(e){cal_order_sum();});
	}
	var el = $('content').getElements('.bDiv');
	if(el){
		el.addEvent('selected', function(){
			cal_order_sum();
		});
	}
});
function show_inventory_qty(el){
	new mBox.Tooltip({
		content: '点击查看',
		pointer: ['left', 40],
		attach: el,
		target: 'mouse'
	});
	var tr = el.getParent('tr');
	var id = tr.getElement('input[col=pid]').get('value');
	new mBox.Tooltip({
		load: 'ajax',
		title: '查看库存：',
		url: APP+'/products/getStockQty/id/'+id,
		setStyles: {content: {padding: 15, lineHeight: 20}},
		width: 300,
		height: 100,
		offset: {
			y: -10
		},
		preventDefault: true,
		attach: el,
		event: 'click',
		pointer: ['left', 40],
		closeOnBodyClick: true
	});
}
function cal_order_sum(){
	var order_sum = 0;
	var other_price = 0;
	var val,qty,freight;
	$('content').getElements('.bDiv tbody tr').each(function(el, index){
		val = el.getElement('input[col=sale_price]').get('value');
		qty = el.getElement('input[col=qty]').get('value').toFloat();
		if(val == '')val = 0;
		order_sum += qty*val.toFloat();
		val = el.getElement('input[col=other_price]').get('value');
		if(val == '')val = 0;
		other_price += qty*val.toFloat();
	});
	freight = $('freight').get('value');
	if(freight == '')freight = 0;
	freight = freight.toFloat();
	$('sum').set('value', formatNumber(order_sum));
	$('other_fund').set('value', formatNumber(other_price));
	$('total').set('value', formatNumber(order_sum+other_price+freight));
}
function getDiscount(el){
	new mBox.Tooltip({
		content: '点击查看',
		pointer: ['left', 40],
		attach: el,
		target: 'mouse'
	});
	new mBox.Tooltip({
		load: 'ajax',
		title: '销售折扣：',
		url: 'ajaxurl',
		setStyles: {content: {padding: 15, lineHeight: 20}},
		width: 200,
		height: 100,
		offset: {
			y: -10
		},
		preventDefault: true,
		attach: el,
		event: 'click',
		pointer: ['left', 40],
		closeOnBodyClick: true,
		ajaxUrl: function(){
			var tr = el.getParent('tr');
			var id = tr.getElement('input[col=pid]').get('value');
			var com = $('content').getElement('.company_id');
			var aid = 0;
			if(com)aid = com.get('value').toInt();
			return APP+'/products/getDiscount/product_id/'+id+'/company_id/'+aid;
		}
	});
}