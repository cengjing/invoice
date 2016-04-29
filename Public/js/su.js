var Select = new Class({
	Extends: DialogBox,
	initialize: function(id, name, isMask, drag_handle, single, singleId){
		this.parent(name, drag_handle,isMask);
		this.randId = id;
		this.winId = 'su-' + this.randId;
		this.users = $$('#win_select_users_' + this.randId + ' .p');
		this.dpts = $$('#win_select_users_' + this.randId + ' .d');
		this.input = $('su-' + this.randId).getElement('.su_show_input');
		this.allBtn = $('su_button_all_'+this.randId);
		this.searchField = $('search_field_'+this.randId);
		this.searchBtn = $('search_btn_'+this.randId);
		if(single){
			this.allBtn.set('disabled', 'true');
			this.single = true;
			this.singleId = singleId;
		}else{
			this.single = false;
		}
		this.searchEvent();
		this.dpts.each(function(el){
			el.addEvent('click',function(){
				this.checkDpt(el);
			}.bind(this));
		}.bind(this));
		this.users.each(function(el){
			el.addEvent('click',function(){
				this.checkUser(el);
			}.bind(this));
		}.bind(this));
	},
	searchEvent: function(){
		this.searchBtn.addEvent('click', function(){
			var value = this.searchField.get('value');
			if(value == '')return;
			$('win_select_users_' + this.randId).getElements('label').each(function(el){
				el.setStyle('color', el.get('html').test(value)?'red':'black');
			});
		}.bind(this));
	},
	load: function(ids){
		if(ids == '')return;
		ids = ids.split(',');
		var user,id;
		ids.each(function(item){
			if(item!=''){
				user = this.Box.getElement('.u_'+item);
				if(user){
					user.setProperty ('checked',true);
					this.checkUser(user);
				}
			}
		}.bind(this));
	},
	checkAll: function(ele){
		if(this.single)return;
		var isChecked = ele.checked;
		this.users.each(function(el){
			el.checked = isChecked;
		});
		this.dpts.each(function(el){
			el.checked = isChecked;
		});
	},
	checkDpt: function(ele){
		if(this.single){
			ele.checked=false
			return;
		}
		var isChecked = ele.checked;
		var flag = false;
		$$('#win_select_users_' + this.randId + ' .d_' + ele.value).each(function(el){
			flag = true;
			el.checked = isChecked;
		});
		if(!flag)ele.checked=false;
		isChecked=true;
		this.users.each(function(el){
			if(!el.checked)isChecked=false;
		});
		this.allBtn.checked = isChecked;
	},
	checkUser: function(ele){
		if(this.single){
			this.users.each(function(el){
				if(ele != el)
					el.checked = false;
			});
			return;
		}
		var isChecked = true;
		var dId = '';
		this.users.each(function(el){
			if(!el.checked)
				isChecked = false;
		});
		this.allBtn.setProperty ('checked',isChecked);
		ele.get('class').split(' ').each(function(c){
				var d = c.split('_');
				if(d.length == 2 && d[0] == 'd')dId=d[1];
		});
		isChecked = true;
		$$('#win_select_users_' + this.randId + ' .d_'+dId).each(function(el){
			if(!el.checked)isChecked=false;
		});
		this.dpts.each(function(el){
			if(el.value == dId)el.checked = isChecked;
		});
	},
	submit: function(show){
		this.input.empty();
		var result;
		var allChecked = this.allBtn.getProperty('checked');
		if(allChecked){
			$(this.winId).getElement('.su_all').set('value',allChecked);
			this.newDiv('全部');
		}
		result = '';
		$$('#win_select_users_' + this.randId + ' input.d').each(function(el){
			if(el.checked){
				result += ((result == '')?'':',') + el.value;
			}
		});
		$(this.winId).getElement('.su_department').set('value',result);
		result = '';
		$$('#win_select_users_' + this.randId + ' input.p').each(function(el){
			if(el.checked){
				result += ((result == '')?'':',') + el.value;
				if(!allChecked){
					this.newDiv(el.getParent('div').get('text'));
				}
				if(this.single){
					if($(this.singleId))$(this.singleId).set('value', el.value);
				}
			}
		}.bind(this));
		$(this.winId).getElement('.su_others').set('value',result);
		this.close();
	},
	newDiv: function(name){
		this.input.adopt(new Element('div', {'class': 'su_box'}).set('text',name.trim()));
	},
	clear: function(){
		this.input.empty();
		$(this.winId).getElement('.su_all').set('value','');
		$(this.winId).getElement('.su_department').set('value','');
		$(this.winId).getElement('.su_others').set('value','');
		$(this.winId).getElement('.win_select_users').getElements('input[type=checkbox]').each( function(el,index){
			el.set('checked', false);
		});
	}
});