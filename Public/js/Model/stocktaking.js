window.addEvent('domready', function(){
	if($('export')){
		$('export').addEvent('click',function(e){
			e.stop();
			if($('warehouse_id').get('value') == 0){
				noti.show({ 
				    title: '提示', 
				    message: '请选择要盘点的库存名称！'
				}); 
				return;
			}
			window.location = APP + "/stocktaking/toExcel/warehouse_id/"+$('warehouse_id').get('value');
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