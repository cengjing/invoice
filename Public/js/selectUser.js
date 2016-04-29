var select = new Class({
	initialize: function(id){
		this.users = $$('#su_userPanel_'+id+' .p');
		this.dpts = $$('#su_userPanel_'+id+' .d');
		this.winId = 'su_userPanel_'+id;
		this.win = $('su_userPanel_'+id);
		this.input = $('su_select_user_'+id);
		this.id = id;
	},
	load: function(ids){
		var all = $('su_all_'+this.id).get('value');
		if(all == 1){
			$('su_button_all_'+this.id).checked = true;
			this.users.each(function(el){
				el.checked = true;
			});
			this.dpts.each(function(el){
				el.checked = true;
			});
			return;
		}
		var ids = $('su_others_'+this.id).get('value');
		if(ids == '')return;
		ids = ids.split(',');
		var user,id;
		ids.each(function(item){
			if(item!=''){
				user = this.win.getElement('.u_'+item);
				user.setProperty ('checked',true);
				this.checkUser(user);
			}
		}.bind(this));
		
	},
	checkAll: function(ele){
		var isChecked = ele.checked;
		this.users.each(function(el){
			el.checked = isChecked;
		});
		this.dpts.each(function(el){
			el.checked = isChecked;
		});
	},
	checkDpt: function(ele){
		var isChecked = ele.checked;
		var els = '#'+this.winId+' .d_' + ele.value;
		var flag = false;
		$$(els).each(function(el){
			flag = true;
			el.checked = isChecked;
		});
		if(!flag)element.checked=false;
		isChecked=true;
		this.users.each(function(el){
			if(!el.checked)isChecked=false;
		});
		$('su_button_all_'+this.id).checked = isChecked;
	},
	checkUser: function(ele){
		var isChecked = true;
		var dId ='';
		this.users.each(function(el){
			if(!el.checked)isChecked=false;
		});
		$('su_button_all_'+this.id).setProperty ('checked',isChecked);
		ele.get('class').split(' ').each(function(c){
				var d = c.split('_');
				if(d.length == 2 && d[0] == 'd')dId=d[1];
		});
		isChecked = true;
		$$('#'+this.winId+' .d_'+dId).each(function(el){
			if(!el.checked)isChecked=false;
		});
		this.dpts.each(function(el){
			if(el.value == dId)el.checked = isChecked;
		});
	},
	submit: function(close){
		this.input.empty();
		var result;
		var allChecked = $('su_button_all_'+this.id).getProperty('checked');
		$('su_all_'+this.id).set('value', allChecked?1:0);
		if(allChecked){
			$('su_all_'+this.id).set('value',allChecked);
			this.newDiv('[ 全部 ]');
		}
		result='';
		$$('#'+this.winId+' input.d').each(function(el){
			if(el.checked){
				result += ',' + el.value;
				if(!allChecked){
					this.newDiv('[ '+el.getParent('div').get('text').trim()+' ]');
				}
			}
		}.bind(this));
		$('su_department_'+this.id).set('value',result);
		result='';
		$$('#'+this.winId+' input.p').each(function(el){
			if(el.checked){
				result += ',' + el.value;
				if(!allChecked){
					this.newDiv(el.getParent('div').get('text').trim());
				}
			}
		}.bind(this));
		$('su_others_'+this.id).set('value',result);
		var results='';
		this.input.getElements('div').each(function(item){
			if(item){results += ' '+item.get('text');}	
		});
		$('su_content_'+this.id).set('value', results);
		if(close)seerph.group.close();
	},
	newDiv: function(name){
		this.input.adopt(new Element('div', {'class': 'su_box'}).set('text',name));
	},
	clear: function(){
		this.input.empty();
		$('su_all_'+this.id).set('value','');
		$('su_department_'+this.id).set('value','');
		$('su_others_'+this.id).set('value','');	
	},
	close: function(){
		seerph.group.close();
	}
});