window.addEvent('domready', function(){
	getComment();
	$('Btn_addComment').addEvent('click',function(){
		addComment();
	});
});
var obj_tmp="";
var ajaxPage = function(url){
	new Request.HTML({
		url: url,
		format: 'page',
		update: $('comments'),
		userSpinner: true,
		onSuccess: function(){
			getCommentSuccess();
			new Fx.Scroll(window).toElement('comments');
		}
	}).send();
}
function getComment(){
	if(!obj_tmp){
		obj_tmp=$('subreply').clone(true,true);
		$('subreply').dispose();
	}
	new Request.HTML({
		url: APP+'/comment/getComment/module/'+module+'/recordId/'+recordId,
		format: 'page',
		update: $('comments'),
		onSuccess: function(){
			getCommentSuccess();
		}
	}).send();
}
function getCommentSuccess(){
	$('comments').getElement('.page').clone().inject($('comments'), 'top');
	$$('.page a').each(function(el){
		el.addEvent('click',function(e){
			e.stop();
			ajaxPage(el.get('href'));
		});
	});
}
function addComment(){
	if($('comment').get('value').trim() == ''){
		noti.show({ 
		    title: '提示', 
		    message: '评论内容不能为空'
		}); 
		$('comment').focus();
		return;
	}
	new Request({
		url: APP+'/comment/add',
		data: $('reply'),
		onSuccess: function(txt){
			$('comment').set('value','');
			var results = JSON.decode(txt);
			if(results.status == 1){
				var li = $('comments').getElement('li');
				if(li){
					li = li.clone();
					li.set('id','comment'+results.data.id);
					li.getElement('img').set('src',results.data.img);
					li.getElement('.com_name').set('text',results.data.name);
					li.getElement('.com_content').set('html',results.data.comment);
					li.getElement('.com_reply').set('href',"JavaScript:reply("+results.data.id+")");
					li.getElement('.subcomment').empty();
					li.inject($('comments').getElement('ul'),'top');
				}else{
					getComment();
				}
			}
		}
	}).send();
}
function reply(id){
	if($('subreply'))$('subreply').dispose();
	obj_tmp.getElement('input[name=toId]').set('value', id);
	obj_tmp.inject($('comment'+id).getElement('.subcomment'),'bottom');
	$('subcomment').set('value', '');
}
function replyComment(){
	if($('subcomment').get('value').trim() == ''){
		noti.show({ 
		    title: '提示', 
		    message: '评论内容不能为空'
		}); 
		$('subcomment').focus();
		return;
	}
	new Request({
		url: APP+'/Comment/add',
		data: $('subreplyform'),
		onSuccess: function(txt){
			var results = JSON.decode(txt);
			if(results.status == 1){
				var id = obj_tmp.getElement('input[name=toId]').get('value');
				var ul = $('comment'+id).getElement('ul');
				if(!ul){
					ul = new Element('ul');
					ul.inject($('comment'+id).getElement('.subcomment'));
				}
				ul = $('comment'+id).getElement('ul');
					
				var tmp = obj_tmp.clone();
				var li = new Element('li');
				tmp.getElement('form').dispose();
				var v = tmp.get('html');
				v += '<div class="right" style="width:85%;padding:5px 10px;">'
					+'<div class="clear" style="padding:5px 10px; border-bottom: 1px dashed #EEE;color:gray;"><span class="com_name">'+results.data.name+'：</span>'
					+'<span class="right">'+results.data.date+'</span></div>'
					+'<div class="clear com_content" style="padding:20px;">'+results.data.comment+'</div></div><div class="clear"></div>';
				tmp.set('html',v);
				tmp.inject(li);
				li.inject(ul,'bottom');
			}
			if($('subreply'))$('subreply').dispose();
		}
	}).send();
}
function deleteReply(){
	if($('subreply'))$('subreply').dispose();
}