var selectBox = new Class({
	id: null,
	pannel: null,
	content: null,
	list: null,
	handle: null,
	open: false,
	initialize: function(id, allSelect){
		this.id = id;
		this.pannel = $(id);
		this.content = this.pannel.getElement('.check-content');
		this.list = this.pannel.getElement('.check-list');
		this.handle = this.pannel.getElement('.check-drag-handle');
		window.addEvent('resize', this.setPosition.bind(this));
		this.content.addEvent('click', function(e){
			e.stop();
			this.setPosition();
			this.handle.setStyle('bottom', 0);
			this.list.setStyle('display', (this.list.getStyle('display') == 'block')? 'none':'block');
			if(this.list.getStyle('display') == 'block'){
				$$('.check-box').each(function(el, indxe){
					if(el != this.pannel)
						el.getElement('.check-list').setStyle('display', 'none');
				}.bind(this));
			}
		}.bind(this));
		window.addEvent('click', this.closeList.bind(this));
		var val = this.pannel.getElement('input').get('value');
		var divs = this.list.getElements('div.row');
		var nodes = this.list.getElements('div.node');
		this.list.getElements('li').addEvents({
			'mouseenter': function(){
				this.setStyle('background-color','#EEE');
			},
			'mouseleave': function(){
				this.setStyle('background-color','#FFF');
			}
		});
		divs.each( function(el,index){
			if(index>0){
				//el.setStyle('width', this.list.getStyle('width').toInt() - el.getPrevious('div').getStyle('width').toInt() - el.getPrevious('div').getStyle('margin-left').toInt()*12 +'px');
			}
			el.addEvents({
				'click': function(e){
						e.stopPropagation();
						/**if(el.getPrevious('div').hasClass('node')){
							this.collapseNode(el);
						}**/
						var li = el.getParent('li');
						if($chk(allSelect) && allSelect == 'false'){
							var next = li.getNext('li');
							if( next != null && next.getProperty('node') > li.getProperty('node') )	return;
						}
						this.content.set('text', el.get('html'));
						var val = el.getProperty('val');
						if(val == 0) val = '';
						this.pannel.getElement('input').set('value', val).fireEvent('change');
						this.closeList();
					}.bind(this),
			});
			if(val != '' && el.getProperty('val') == val)
				this.content.set('text', el.get('html'));
		}.bind(this));
		nodes.each(function(el, index){
			el.addEvent('click', function(e){
				e.stopPropagation();
				this.collapseNode(el);
			}.bind(this));
			el.addEvent('dbclick',function(e){e.stop();});
		}.bind(this));
		this.list.addEvent('click', function(e){e.stop();});
		this.list.addEvent('scroll', function(e){
			this.handle.setStyle('bottom', -this.list.getScroll().y);
		}.bind(this));
		this.list.getElement('.closeSelect').addEvent('click', this.closeList.bind(this));
		this.list.getElement('.openAll').addEvent('click', function(){
			nodes.each(function(el, index){
				this.collapseNode(el, 'true');
			}.bind(this));
		}.bind(this));
		this.list.getElement('.closeAll').addEvent('click', function(){
			nodes.each(function(el, index){
				this.collapseNode(el, 'false');
			}.bind(this));
		}.bind(this));
		this.addDragHandle();
		var height = (divs.length<=10) ? (divs.length * 20) : 200;
		this.list.setStyle('height', height+'px');
		var val = this.pannel.getElement('input').get('value');
		
	},
	collapseNode: function(el, status){
		var li = el.getParent('li');
		var open = li.getProperty('open');
		if($chk(status)){
			if(open != status)return;
		}
		li.getChildren('.node').setStyle('background-position',(open == 'true') ? '-234px 0' : '-216px 0');
		var node = li.getProperty('node');
		var next = li.getNext('li');
		li.setProperty('open', (open == 'true')?'false':'true');
		var child;
		while (next != null && next.getProperty('node') > node){
			next.setStyle('display', (open == 'true') ? 'none' : 'block');
			if(open == 'false' ){
				var childNode = next.getProperty('node');
				if(next.getProperty('open') == 'false'){
					next = next.getNext('li');
					if(next){
						while (next.getProperty('node') > childNode){
							next = next.getNext('li');
							if(!next)break;
						}
					}
				}else
					next = next.getNext('li');
			}else		
				next = next.getNext('li');
		}
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
	addDragHandle: function(){
		this.list.makeResizable({
			snap: 0,
			handle: this.handle,
			limit: {x: [160, 400], y: [20, 600]},
			stopPropagation: true,
			preventDefault: true
		});
	}
});