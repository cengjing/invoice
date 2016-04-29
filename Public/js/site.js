var noti = {}; 
window.addEvent('domready', function(){
	noti = new Notimoo();
	mBox_search = new mBox.Tooltip({
		load: 'ajax',
		title: '查询结果：',
		closeInTitle: true,
		setStyles: {content: {padding: 15}},
		width: 360,
		height: 280,
		offset: {x:0,y:5},
		position:{x:['center'],y:['bottom', 'outside']},
		preventDefault: false,
		//attach: $('fBtn'),
		target: $('fIpt'),
		event: 'click',
		pointer: ['top', 0],
		closeOnBodyClick: true,
		constructOnInit:false
	});
	$('fIpt').addEvent('keydown', function(e){
		if(e.key == 'enter')
		{
			var u = APP+'/index/search/s/'+$('fIpt').get('value');
			mBox_search.options.url = u;
			mBox_search.ajaxLoaded = false;
			mBox_search.isOpen = false;
			mBox_search.open();
		}
	});
	if($('submit_insert')){
		$('submit_insert').addEvent('click', function(){
			saveFormData(URL+'/insert');
		});
	}
	if($('submit_update')){
		$('submit_update').addEvent('click', function(){
			saveFormData(URL+'/update');
		});
	}
	$('body').addEvent('mouseenter:relay(div .plist)',function(e,target){
		target.set('tween', {duration: 200});
		target.tween('background-color', '#F9F9F9');
	});
	$('body').addEvent('mouseleave:relay(div .plist)',function(e,target){
		target.set('tween', {duration: 500});
		target.tween('background-color', '#FFFFFF');
	});
});

var DialogBox = new Class({
	display		: false,
	isMask		: true,
	mask		: null,
	url			: null,
	Box			: null,
	drag_handle	: null,
	initialize: function(name, drag_handle, isMask){
		this.Box = ($type(name) == 'string') ? $(name) : name;
		this.drag_handle = ($type(drag_handle) == 'string') ? $(drag_handle) : drag_handle;
		this.Box.position().setStyle('display', 'none').fade();
		this.Box.inject($('container'));
		this.isMask = isMask;
		if(this.isMask){
			this.mask = new Mask();
		}
		this.drag();
	},
	show: function(){
		var i = 0;
		$('container').getElements('.win').each(function(el, index){
			if(el.getStyle('display') == 'block')i++;
		});
		if(this.isMask){
			this.mask.element.setStyle('z-index', 999+i*2);
		}
		this.Box.setStyle('z-index', 1000+i*2);
		this.display = true;
		this.Box.position().fade('in');
		if(Browser.Engine.trident4)
			$$('select', 'object', 'embed').each(function(node){ node.setStyle('visibility', 'hidden')});
		this.Box.setStyle('display', 'block');
		if(this.isMask){
			this.mask.show();
		}
	},
	close: function(){
		this.display = false;
		this.Box.fade('out').setStyle('display', 'none');
		if(Browser.Engine.trident4)
	        $$('select', 'object', 'embed').each(function(node){ node.setStyle('visibility', 'visible')});
		if(this.isMask){
			this.mask.hide();
		}
	},
	reload: function(url,updateElement){
		if(!$chk(url))
			url = this.url;
		new Request.HTML({
			url:url,
			format: 'page',
			update: $(updateElement),
			useSpinner: true,
			onSuccess: function(){
				this.show();
				this.drag();
			}.bind(this)
		}).send();
	},
	drag: function(){
		if(!$(this.drag_handle))return;
		var size = window.getSize();
		var w = this.Box.getStyle('width').toInt() + 40;
		var h = this.Box.getStyle('height').toInt() + 40;
		var limit_y = size.y - h;
		if(limit_y<h)limit_y=h+40;
		new Drag(this.Box,{
			snap	: 0,
			handle 	: this.drag_handle,
			limit	: {x: [40, size.x - w], y: [40, limit_y]}
		});
	}
});

function toggle(slide,toggle){
	var slide = new Fx.Slide(slide);
	slide.hide();
	$(toggle).addEvent('click', function(e){
		e.stop();
		slide.toggle();
	});
}
function addFile(){
	var div = new Element('div');
	new Element('input',{name:'attachment[]', type:'file'}).inject(div);
	div.inject($('uploadFiles'));
}
var Grid = new Class({
	id: '',
	headers: null,
	drag: null,
	hDiv: null,
	bDiv: null,
	hDivHeight: null,
	bDivHeight: null,
	checkAll: null,
	checkItem: null,
	rowData: null,
	sort: null,
	initialize: function(id){
		this.id = id;
		this.headers = $$('#' + this.id + ' .hDiv th');
		this.drag = $$('#' + this.id + ' .cDrag');
		this.hDiv = $(this.id).getElement('.hDiv');
		this.bDiv = $(this.id).getElement('.bDiv');
		this.hDivHeight = this.hDiv.getStyle('height').toInt();
		this.bDivHeight = this.bDiv.getStyle('height').toInt();
		this.checkAll = $(this.id).getElement('.check-all');
		this.checkItem = $$('#' + this.id + ' .grid-item');
		this.bDiv.addEvent('scroll', this.setDragLine.bind(this));
		
		$$('#' + this.id + ' .bDiv tr:nth-child(even)').addClass('row');
		this.setDragLine();
		$$('#' + this.id + ' .cDrag div').each(function(el, index){
			new Drag(el,{
				 snap: 0,
				 modifiers: {x: 'left', y: ''},
				 onSnap: function(ele){ ele.addClass('dragging'); },
				 onComplete : function(ele){
					ele.removeClass('dragging'); 
				 	var left = ele.getStyle('left').toInt();
				 	var widthTotal = 0;
				 	this.headers.each(function(th, idx){
					 	if(idx < index)
					 		widthTotal += th.getStyle('width').toInt() + 2;
				 		if(idx == index){
					 		var widthFixed = left - widthTotal -5- el.getStyle('width').toInt() + this.bDiv.scrollLeft;
					 		th.getElement('div').setStyle('width', widthFixed);
					 		$$('#' + this.id + ' .bDiv tr td:nth-child('+(idx+1)+')').each(function(element, item){
						 		element.getElement('div').setStyle('width', widthFixed);
						 	});
					 		this.setDragLine();
				 		}
					}.bind(this));
				 }.bind(this)
			});
		}.bind(this));
		if(this.checkAll){
			this.checkAll.addEvent('click',function(){
				$$('#' + this.id + ' .grid-item').each(function(el){
					var sel = this.checkAll.get('checked');
					el.set('checked', sel).fireEvent('click');
					if(sel){
						el.getParent('tr').addClass('row-selected');
					}else{
						el.getParent('tr').removeClass('row-selected');
					}
				}.bind(this));
			}.bind(this));
		}
		this.bDiv.addEvent('click:relay(#' + this.id + ' .grid-item)',function(){
			var tr = this.getParent('tr');
			tr.toggleClass('row-selected');
		});
		var el = $(this.id).getElement('.row-data'); 
		if(el){
			this.rowData = el.removeClass('row-data').setStyle('display','').clone(true);
			el.dispose();
		}
	},
	sortable: function(){
		this.sort = new Sortables(this.bDiv.getElement('tbody'), {
			clone: true,
			revert: true,
			handle: '.grid_item',
			onStart: function(ele,clone){
				clone.getElement('.grid_item').removeClass('grid_item');
				clone.setStyle('border','1px solid #000');
				clone.removeClass('rowItem');
			},
			onComplete: function(){
				this.setOrder();
				this.setEvenRow();
			}.bind(this)
		});
	},
	addSortableItems: function(el){
		if(!this.sort)return;
		this.sort.addItems(el);
	},
	setOrder: function(){
		this.bDiv.getElements('.grid_item').each(function(el,index){
			el.set('html',index+1);
		});
	},
	setEvenRow: function(){
		this.bDiv.getElements('.rowItem').removeClass('row');
		this.bDiv.getElements('.rowItem:nth-child(even)').addClass('row');
	},
	setDragLine: function(){
		var leftOffset = 0;
		var l_scrollerX = this.bDiv.getScroll().x;
		this.hDiv.scrollLeft = this.bDiv.scrollLeft;
		this.bDivHeight = this.bDiv.getStyle('height').toInt();
		this.headers.each(function(el,index){
			var width = el.getStyle('width').toInt();
			var height = this.hDivHeight + this.bDivHeight;
			this.drag.getElement(':nth-child('+(index+1)+')').setStyles({
				'left': width + leftOffset - l_scrollerX,
				'height': height
				});
			leftOffset += width + 2;
		}.bind(this));
	}
});
function formatNumber(number){
	return number.toFloat().format({decimals:2,group: ""});
}
function formatGroupNumber(number){
	return number.toFloat().format({decimals:2,group: ","});
}
function saveFormData(url){
	var el = $('content');
	new Request({
		url: url,
		data: el,
		onRequest: function(){
			el.spin();
		},
		onSuccess: function(txt){
			el.unspin();
			results = JSON.decode(txt);
			if(results.status == 1){
				window.location= URL+"/show/id/" + results.data.id ;
			}else{
				noti.show({ 
				    title: '提示', 
				    message: results.info 
				}); 
			}
		}
	}).send();
}
function startFileUpload(){
	if($('file_upload_btn').get('value') == ''){
		noti.show({ 
		    title: '提示', 
		    message: '请选择要上传的文件。'
		}); 
		return false;
	}
    $('file_upload_process').setStyle('visibility', 'visible');
    $('file_upload_form').setStyle('visibility', 'hidden');
    return true;
}
function stopFileUpload(attachId, error){
	if(attachId > 0){
		var el = $('file_list');
		new Request.HTML({
			url: APP+'/common/reloadFiles',
			data: $('file_upload_form'),
			update: el,
			onSuccess: function(){
				$('file_upload_process').setStyle('visibility', 'hidden');
				$('file_upload_form').setStyle('visibility', 'visible');
				$('file_upload_btn').set('value','');
			}
		}).send();
	}else{
		$('file_upload_process').setStyle('visibility', 'hidden');
		$('file_upload_form').setStyle('visibility', 'visible');
		noti.show({ 
		    title: '上传失败', 
		    message: error
		}); 
	}
    return true;   
}
function deleteAttach(id){
	if(confirm('您确定要删除附件吗？')){
		if(id.toInt()<=0)return;
		new Request({
			url: APP+'/common/deleteFile/id/'+id,
			onSuccess: function(txt){
				results = JSON.decode(txt);
				if(results.status == 1){
					$('attach_'+id).dispose();
				}else{
					noti.show({ 
					    title: '提示', 
					    message: results.info 
					}); 
				}
			}
		}).send();
	}
}