var multiSelectBox = new Class({
	id: null,
	pannel: null,
	content: null,
	list: null,
	selectAllBtn: null,
	open: false,
	initialize: function(id){
		this.id = id;
		this.pannel = $(id);
		this.content = this.pannel.getElement('.check-content');
		this.list = this.pannel.getElement('.check-list');
		this.selectAllBtn = this.pannel.getElement('.btn-check-all');
		window.addEvent('resize', this.setPosition.bind(this));
		this.content.addEvent('click', function(e){
			e.stop();
			this.setPosition();
			this.list.setStyle('display', (this.list.getStyle('display') == 'block')? 'none':'block');
			if(this.list.getStyle('display') == 'block'){
				$$('.check-box').each(function(el, indxe){
					if(el != this.pannel)
						el.getElement('.check-list').setStyle('display', 'none');
				}.bind(this));
			}
		}.bind(this));
		window.addEvent('click', this.closeList.bind(this));
		this.list.getElements('li').addEvents({
			'mouseenter': function(){
				this.setStyle('background-color','#EEE');
			},
			'mouseleave': function(){
				this.setStyle('background-color','#FFF');
			}
		});
		this.list.addEvent('click', function(e){e.stopPropagation(); this.selectNum();}.bind(this));
		this.list.getElement('.closeSelect').addEvent('click', this.closeList.bind(this));
		this.selectNum();
		this.selectAllBtn.addEvent('click', function(e){
			e.stop();
			var val = this.selectAllBtn.hasClass('all-selected')?false:true;
			this.list.getElements('input[type=checkbox]').each(function(el){el.set('checked',val)});
			this.selectAllBtn.toggleClass('all-selected');
			this.selectAllBtn.set('html',(val==true)?'全不选':'全选');
			this.selectNum();
		}.bind(this));
	},
	openList: function(e){
		e.stop();
		this.list.setStyle('display', 'block');
	},
	closeList: function(){
		this.list.setStyle('display', 'none');
	},
	setPosition: function(){
		this.list.position({
			relativeTo: this.pannel,
			position: 'bottomLeft',
			offset: {x: 0, y: 1}
		});
	},
	selectNum: function(){
		var qty = 0;
		this.list.getElements('input[type=checkbox]').each(function(el){
			if(el.get('checked') == true)
			{
				qty++;
			}
		});
		this.content.set('html',(qty == 0) ? '请选择...' : qty + ' 个选项');
	}
});