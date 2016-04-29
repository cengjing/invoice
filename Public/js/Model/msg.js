window.addEvent('domready',function(){
	$$('.inbox-others').each(function(el){
		var id = el.getProperty('msg');
		new mBox.Tooltip({
			content: '点击查看',
			pointer: ['left', 40],
			attach: el,
			target: 'mouse'
		});
		new mBox.Tooltip({
			load: 'ajax',
			title: '相关人：',
			url: APP+'/notify/getOthers/id/'+id,
			setStyles: {content: {padding: 15, lineHeight: 20}},
			width: 500,
			height: 400,
			offset: {
				x: 20
			},
			position:{x:['right', 'outside'],y:'center'},
			preventDefault: true,
			attach: el,
			event: 'click',
			pointer: ['left', 40],
			closeOnBodyClick: true,
			closeInTitle: true
		});
	});
	$$('.delete').addEvent('click', function(e){
		if(!confirm('您确定要删除吗？')){
			e.stop();
		}
	});
});