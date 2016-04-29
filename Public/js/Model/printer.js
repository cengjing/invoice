var fields = {
		'from'			: '寄件人',
		'to'			: '收件人',
		'from_tel'		: '寄件人电话',
		'to_tel'		: '收件人电话',
		'from_comp'		: '寄件单位',
		'to_comp'		: '收件单位',
		'from_addr'		: '寄件地址',
		'to_addr'		: '收件地址',
		'from_postal'	: '寄件邮编',
		'to_postal'		: '收件邮编',
		'from_date'		: '寄件日期'
};
window.addEvent('domready', function(){
	var bg = $('express_bg');
	var id,div;
	if(bg){
		var pro = bg.getSize().x/bg.getStyle('width').toFloat();
		$('bg_width')
		.set('value', bg.getStyle('width').toFloat())
		.addEvent('change', function(){
			bg.setStyle('width', this.get('value').toFloat()+'mm');
		});
		$('bg_height')
		.set('value', bg.getStyle('height').toFloat())
		.addEvent('change', function(){
			bg.setStyle('height', this.get('value').toFloat()+'mm');
		});;
		$('font_size').addEvent('change', function(){
			var val = this.get('value');
			if(val != 0){
				var selectedBox = bg.getElement('.selected-box');
				selectedBox.setStyle('font-size', val);
			}
		})
		$('font_weight').addEvent('click', function(e){
			var val = this.get('checked');
			var selectedBox = bg.getElement('.selected-box');
			if(selectedBox){
				selectedBox.setStyle('font-weight', val==true?'bold':'normal');
			}
		});
		$('fields').addEvent('change', function(){
			var val = this.get('value');
			if(val == '') return;
			if($('col_'+val)){
				$$('.resize-box').removeClass('selected-box');
				$('col_'+val).addClass('selected-box');
				return;
			}
			addLabel(val,this.options[this.selectedIndex].text);
		}).set('value', '');
		$('submit').addEvent('click',function(){
			$('templ').set('value', saveLabel());
			new Request({
				url: $('form').get('action'),
				data: $('form'),
				userSpinner: true,
				onRequest: function(){
				},
				onSuccess: function(txt){
					var results = JSON.decode(txt);
					if(results.status == 1){
						window.location= APP+"/printer/edit/id/" + results.data.id;
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
});
function addLabel(name, text, style){
	id = 'col_'+name;
	if($chk(text) || text == ''){
		text = fields[name];
	}
	div = new Element('div',{
		'id'		: id,
		'class'		: 'resize-box'
	}).set('text',text);
	if($chk(style))div.setStyles(style);
	rv = new Element('div', {id: 'r_'+name, 'class': 'resize-handle'}).inject(div);
	div.inject($('express_bg'))
	.addEvent('click', function(){
		$$('.resize-box').removeClass('selected-box');
		this.addClass('selected-box');
		$('font_size').set('value', this.getStyle('font-size'));
		$('font_weight').set('checked', this.getStyle('font-weight') == 'normal'?false:true);
		var selectedContent = this.get('text');
		var select = $('fields');
		for(i=0;i <select.length;i++){ 
			if(select.options[i].text == selectedContent ){
				select.options[i].selected = true;
			}
		}
	})
	.makeResizable({
		snap: 0,
		handle: $('r_'+name),
		limit: {x: [20, 400], y: [20, 100]},
		stopPropagation: true,
		preventDefault: true
	});
	new Drag(div, {
		snap: 0,
		limit: {x: [0, 800], y: [0, 500]}
	});
}
function saveLabel(){
	var con = [];
	$$('.resize-box').each(function(el,index){
		con.extend([{
			'id'			: el.get('id'),
			'width'			: el.getStyle('width'),
			'height'		: el.getStyle('height'),
			'left'			: el.getStyle('left'),
			'top'			: el.getStyle('top'),
			'fontSize'		: el.getStyle('font-size'),
			'fontWeight'	: el.getStyle('font-weight')
		}]);
	});
	return JSON.encode(con);
}
function deleteLabel(){
	var label = $('express_bg').getElement('.selected-box');
	if(label){
		label.dispose();
	}
}
function startUpload(){
	if($('myfile').get('value') == '')
	{
		noti.show({ 
		    title: '提示', 
		    message: '请选择模板图片'
		}); 
		return false;
	}
    $('upload_process').setStyle('visibility', 'visible');
    $('upload_form').setStyle('visibility', 'hidden');
    return true;
}
function stopUpload(attachId,savePath){
	if(attachId > 0){
		$('express_bg').setStyle('background-image', savePath);
		$('attach_id').set('value',attachId);
	    $('upload_process').setStyle('visibility', 'hidden');
	    $('upload_form').setStyle('visibility', 'visible');
	}
    return true;   
}