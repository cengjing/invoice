<?php

class IndexAction extends GlobalAction
{
public function index() 
{
$this->setTitle('首页');
import('ORG.Util.String');
$M = M();
$A = $this->prefix.'news A';
$B = $this->prefix."news_read B";
$this->assign('news',$vo);
$A = $this->prefix.'members A';
$B = $this->prefix."feedback B";
$Sql = "SELECT A.is_read,B.id,B.title,B.cTime,B.uid FROM $A LEFT JOIN $B ON A.recordId=B.id
				WHERE A.toUserId=$this->uid AND B.status=1 AND A.type='feed' 
				ORDER BY A.is_read ASC,A.readTime DESC,B.cTime DESC LIMIT 10";
$this->assign('feeds',$vo);
$access = $this->__get('access');
if (isset($access['SALESORDER']['INDEX']))
{
$A = $this->prefix.'salesorder A';
$B = $this->prefix."accounts B";
$Sql = "SELECT A.id,A.cTime,A.abstract,A.assignto,A.total,A.status,B.company
					FROM $A LEFT JOIN $B ON (A.company_id=B.id) 
					WHERE (A.uid=$this->uid OR A.assignto=$this->uid) 
					ORDER BY A.id DESC LIMIT 10";
$vo = $M->query($Sql);
$this->assign('orders',$vo);
$Sql = "SELECT COUNT((A.total-A.collection)>0) c1,COUNT((A.total-A.invoice)>0) c2,
					SUM(A.total-A.collection) collection,SUM(A.total-A.invoice) invoice FROM $A WHERE A.assignto=$this->uid AND A.status=3";
$vo = $M->query($Sql);
$this->assign('orderStat',$vo[0]);
$Sql = "SELECT SUM(A.total) amount,FROM_UNIXTIME(A.cTime,'%Y/%m') time FROM $A 
					WHERE A.assignto=$this->uid AND A.status IN(3,5) 
					GROUP BY FROM_UNIXTIME(A.cTime,'%y/%m') LIMIT 10";
$vo = $M->query($Sql);
$vo = list_sort_by($vo,'time','desc');
$this->assign('orderMonthStat',$vo);
$vo = D('GroupUser')->getUsers('salesorder');
if($vo['all'])
{
$Sql = "SELECT COUNT((A.total-A.collection)>0) c1,COUNT((A.total-A.invoice)>0) c2,
						SUM(A.total-A.collection) collection,SUM(A.total-A.invoice) invoice FROM $A WHERE A.status=3";
$vo = $M->query($Sql);
$this->assign('orderAllStat',$vo[0]);
$Sql = "SELECT SUM(A.total) amount,FROM_UNIXTIME(A.cTime,'%Y/%m') time FROM $A WHERE A.status IN(3,5) GROUP BY FROM_UNIXTIME(A.cTime,'%Y/%m') LIMIT 10";
$vo = $M->query($Sql);
$this->assign('orderMonthAllStat',$vo);
}
}
if (isset($access['PURCHASE']['INDEX']))
{
$A = $this->prefix.'purchase A';
$Sql = "SELECT COUNT((A.amount-A.payment)>0) c1,COUNT((A.amount-A.invoice)>0) c2,
					SUM(A.amount-A.payment) payment,SUM(A.amount-A.invoice) invoice FROM $A WHERE A.status=3";
$vo = $M->query($Sql);
$this->assign('purchaseAllStat',$vo[0]);
}
if (isset($access['STATIC']['WH']))
{
$field = '';
$vo = M('WarehouseName')->where('on_sale=1')->field('dbfield')->select();
foreach ($vo as $val){
$field .= (($field=='')?'':'+').$val['dbfield'];
}
$this->assign('warehouseQty',count($vo));
$A = $this->prefix .'warehouse A ';
$B = $this->prefix .'products B ';
$from 	= " FROM $A LEFT JOIN $B ON A.product_id=B.id";
$Sql	= " SELECT SUM(($field)*B.stock_price) amount $from";
$vo = $M->query($Sql);
$this->assign('stockStat',$vo[0]);
}
if (isset($access['WAREHOUSE']['SAFE']))
{
$field = '';
$vo = M('WarehouseName')->where('on_sale=1')->field('dbfield')->select();
foreach ($vo as $val){
$field .= (($field=='')?'':'+').$val['dbfield'];
}
$A = $this->prefix.'warehouse A';
$B = $this->prefix.'products B';
$Sql = "SELECT $field qty,B.min_stock_qty FROM $B
					LEFT JOIN $A ON (A.product_id=B.id)
					WHERE B.min_stock_qty>0 AND B.on_sale=1";
$vo = $M->query($Sql);
$c = 0;
foreach ($vo as $val){
if(floatval($val['qty'])<=floatval($val['min_stock_qty']))
$c++;
}
$this->assign('stockSafe',$c);
}
$this->display ();
}
private function decode()
{
$OOO0O0O00='$OOO0O0O00';
$OOO000000='$OOO000000';
$OO00O0000='$OO00O0000';
$OOO0000O0='$OOO0000O0';
$O0O0000O0='$O0O0000O0';
$content="";
if(preg_match("/O0O0000O0\('.*'\)/",$lines[1],$y))
{
$content=str_replace("O0O0000O0('","",$y[0]);
$content=str_replace("')","",$content);
$content=base64_decode($content);
}
$decode_key="";
if(preg_match("/\),'.*',/",$content,$k))
{
$decode_key=str_replace("),'","",$k[0]);
$decode_key=str_replace("',","",$decode_key);
}
$str_length="";
if(preg_match("/,\d*\),/",$content,$k))
{
$str_length=str_replace("),","",$k[0]);
$str_length=str_replace(",","",$str_length);
}
$Secret=substr($lines[2],$str_length);
echo "<?php\n".base64_decode(strtr($Secret,$decode_key,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'))."?>";
}
public function news()
{
$id = intval($_REQUEST['id']);
$model = M('NewsRead');
$map['uid'] = $this->uid;
$map['new_id'] = $id;
$is_read = $model->where($map)->find();
if(count($is_read) == 0)
{
$map['readTime'] = time();
$model->add($map);
}
$vo = M('News')->where(array('id'=>$id))->find();
$this->assign('vo',$vo);
$this->setTitle($vo['title'].' - 新闻');
$this->display();
}
public function newsList()
{
$this->setTitle('新闻');
$A = $this->prefix.'news A';
$B = $this->prefix.'news_read B';
$from = " FROM $A LEFT JOIN $B ON (A.id=B.new_id AND B.uid=$this->uid)";
$countSQL = "SELECT COUNT(*) $from ";
$pageSQL = " SELECT A.*,B.uid AS readUid,B.readTime $from ORDER BY cTime DESC ";
$this->_pageList ( $countSQL,$pageSQL );
$this->display ('newsList');
}
public function search()
{
$result = false;
import('ORG.Util.Input');
$s = Input::getVar($_REQUEST['s']);
if(!is_utf8($s))
{
import('ORG.Util.String');
$s = String::autoCharset($s);
}
$GroupUser = D('GroupUser');
$map['company|abstract'] = array('like',"%$s%");
$group =$GroupUser->getUsers( 'Accounts');
if(!$group['all']){
foreach($group['users'] as $val)
{
$u[] = $val['id'];
$ids .= (($ids=='')?'':',').$val['id'];
}
$map['assignto'] = array('IN',$u);
$filter_user = " AND assignto IN ($ids) ";
}
$vo = M('Accounts')->where($map)->field('id,company,abstract')->limit(20)->select();
$this->assign('accountsList',$vo);
if(count($vo)>0)$result = true;
unset($map);
$A = $this->prefix .'contacts A';
$B = $this->prefix .'accounts B';
$SQL = "SELECT A.id,name,description,company FROM $A LEFT JOIN $B ON (A.company_id=B.id) 
				WHERE (name LIKE '%$s%' OR description LIKE '%$s%') $filter_user LIMIT 20";
$vo = M()->query($SQL);
$this->assign('contactsList',$vo);
if(count($vo)>0)$result = true;
$this->assign('searchResult',$result);
$this->display('filter');
}
}
?>