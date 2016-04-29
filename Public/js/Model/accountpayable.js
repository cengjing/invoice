window.addEvent('domready', function(){
	if($('type')){
		$('type').addEvent('change', function(e){
			var val = this.get('value');
			var ele = $('from_id_lable');
			if( val == 1){
				ele.set('html','<span class="rs">*</span>&nbsp;采购发票');
			}else if( val == 2 ){
				ele.set('html','<span class="rs">*</span>&nbsp;销售订单');
			}else{
				ele.set('text','单据来源');
			}
			
		});
	}
});