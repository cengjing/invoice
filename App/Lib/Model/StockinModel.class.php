<?php

class StockinModel extends Model 
{
protected $name = 'entry';
public function getInfo($id)
{
$vo = $this->where("id=$id")->find();
return $vo;
}
public function getDetails($id)
{
$prefix = C( 'DB_PREFIX');
$B = $prefix.'products B';
$sql = "SELECT SUM(A.qty) qty,A.product_id,B.catno,B.productname,B.description,B.ch_description,B.unit,B.spec
								FROM __TABLE__ A LEFT JOIN $B ON A.product_id=B.id WHERE A.entry_id=$id 
								GROUP BY A.product_id ORDER BY A.product_id ";
$vo = M('EntryDetails')->query($sql);
return $vo;
}
public function getPerm($id)
{
$vo = $this->getInfo($id);
$warehouse_id = $vo['warehouse_id'];
$warehouse = D('Warehouse')->getUserStock();
$perm = false;
foreach ($warehouse as $v)
{
if ($v['id'] == $warehouse_id)
{
$perm = true;
break;
}
}
if ($perm)
{
switch ($vo['type'])
{
case 1:
$vo['from_id'] = "<a target='_blank' href='".u('arrival/show',array('id'=>$vo['from_id']))."'>".$vo['from_id']."</a>";
break;
case 2:
$vo['from_id'] = "<a target='_blank' href='".u('allot/show',array('id'=>$vo['from_id']))."'>".$vo['from_id']."</a>";
break;
case 3:
$vo['from_id'] = "<a target='_blank' href='".u('returns/show',array('id'=>$vo['from_id']))."'>".$vo['from_id']."</a>";
break;
case 4:
$vo['from_id'] = "<a target='_blank' href='".u('stocktaking/show',array('id'=>$vo['from_id']))."'>".$vo['from_id']."</a>";
break;
}
return $vo;
}
else
{
return  false;
}
}
}?>