window.addEvent('domready', function(){
	if($('delete')){
		$('delete').addEvent('click', function(){
			if(!confirm('您确定要删除采购单吗？'))
			{
				e.stop();
			}
		});
	}
	if($('deleteMrpItem')){
		$('deleteMrpItem').addEvent('click', function(){
			if(confirm('您确定要删除选中的Mrp明细吗？'))
			{
				var el = $('mrp_details').getElements('.grid-item');
				if(el.length == 0)return;
				el.each(function(el, index){
					if(el.get('checked')){
						el.getParent('tr').dispose();
					}
				});
				grid_mrp_details.setOrder();
				grid_mrp_details.setEvenRow();
			}
		});
	}
});
function cal_order_sum(){
	var freight,otherFund,sum,ele;
	
	freight = $('freight').get('value');
	if(freight == '')freight = 0;
	freight = freight.toFloat();
	
	otherFund = $('other_fund').get('value');
	if(otherFund == '')otherFund = 0;
	otherFund = otherFund.toFloat();
	
	sum = $('sum').get('value');
	if(sum == '')sum = 0;
	sum = sum.toFloat();
	
	ele = $('amount');
	if(ele)ele.set('value', formatNumber(sum + otherFund + freight));
}