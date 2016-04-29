<?php

class AccountsStatModel extends Model
{
public function stat($id,$type='all')
{
if(empty($id))return;
if( $type == 'salesorder'||$type == 'all')
{
$model = M('Salesorder');
$vo = $model->where(array('company_id'=>$id,'status'=>array('IN','3,5')))->field('cTime')->order('id DESC')->find();
$data['last_order'] = $vo['cTime'];
$vo = $model->query("SELECT SUM(sum) sum FROM __TABLE__ WHERE company_id=$id AND status=5");
if(empty($vo[0]['sum']))$vo[0]['sum'] = 0;
$data['order_amount'] = $vo[0]['sum'];
$vo = $model->where(array('company_id'=>$id,'status'=>5))->count();
$data['order_quant'] = $vo;
$vo = $model->where(array('company_id'=>$id,'status'=>3))->count();
$data['unfinished_order_quant'] = $vo;
}
if( $type == 'contacts'||$type == 'all')
{
$vo = M('Contacts')->where(array('company_id'=>$id,'status'=>1))->count();
$data['contacts_quant'] = $vo;
}
if( $type == 'activity'||$type == 'all')
{
$model = M('Activity');
$vo = $model->where(array('company_id'=>$id))->count();
$data['activiry_quant'] = $vo;
$vo = $model->where(array('company_id'=>$id))->field('activity_time')->order('activity_time DESC')->find();
$data['last_activity'] = $vo['activity_time'];
}
if(count($data)>0)
{
$model = M('AccountsStat');
$vo = $model->where(array('company_id'=>$id))->find();
if($vo == null)
{
$data['company_id'] = $id;
$model->add($data);
}else{
$data['id'] = $vo['id'];
$model->save($data);
}
}
}
}
?>