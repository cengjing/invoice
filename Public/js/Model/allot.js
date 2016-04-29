window.addEvent('domready', function(){
	var inventory = $$('.inventory-qty');
	if(inventory) { inventory.each(function(el){show_inventory_qty(el);}); }
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