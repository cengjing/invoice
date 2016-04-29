window.addEvent('domready', function(){
	var im = $$('.import');
	if(im) { import_backup(im); }
	var de = $$('.delete');
	if(de) { delete_backup(de); }
})
function import_backup(el){
	el.addEvent('click', function(){
		if(confirm('您确定要导入数据么？之前的数据将被覆盖！')){
			var div = this.getParent('div');
			var val = div.getElement('input').get('value');
			window.location = APP+'/backup/import/filename/'+val;
		}
	});
}
function delete_backup(el){
	el.addEvent('click', function(){
		if(confirm('您确定要删除备份数据么？')){
			var div = this.getParent('div');
			var val = div.getElement('input').get('value');
			window.location = APP+'/backup/delete/filename/'+val;
		}
	});
}