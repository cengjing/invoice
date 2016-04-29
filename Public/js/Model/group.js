function redirectUser(){
	var value = $('container').getElement('input[name=others]').get('value');
	value = ( value == "" ) ? 0 : value.toInt();
	if(value > 0){
		window.location = APP + '/group/show/uid/'+value;
	}else{
		noti.show({ 
		    title: '提示', 
		    message: '请选择一个用户' 
		}); 
	}
}