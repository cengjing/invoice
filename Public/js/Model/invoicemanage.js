window.addEvent('domready', function(){
	if($('submit')){
		$('submit').addEvent('click', function(e){
			var form = $('content');
			new Request({
				url: APP+"/invoicemanage/update",
				data: form,
				userSpinner: true,
				onRequest: function(){
					form.spin();
				},
				onSuccess: function(txt){
					form.unspin();
					results = JSON.decode(txt);
					if(results.status == 1){
						window.location = APP+"/invoicemanage/index";
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
	if($('delete')){
		$('delete').addEvent('click', function(){
			if(confirm('您确定要删除这张发票申请码？')){
				new Request({
					url: APP+'/Invoicemanage/delete',
					data: $('content'),
					userSpinner: true,
					onRequest: function(){
					},
					onSuccess: function(txt){
						results = JSON.decode(txt);
						if(results.status == 1){
							window.location= APP+"/InvoiceManage/index";
						}else{
							noti.show({ 
							    title: '提示', 
							    message: results.info 
							}); 
						}
					}
				}).send();
			}
		});
	}
});