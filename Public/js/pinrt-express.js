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
	div.inject($('express_bg'));
}