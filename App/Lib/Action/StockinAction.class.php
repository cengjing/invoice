<?php

class StockinAction extends GlobalAction 
{
public function index()
{
$this->setTitle('入库单列表');
$warehouse = D('Warehouse')->getUserStock();
$ws = '';
foreach ($warehouse as $val)
{
$ws .= (($ws == '') ?'': ',') .$val['id'];
}
$ret = $this->getFilter('Stockin','A.',true);
if(!isset($_REQUEST['status']))
{
$status = ' AND A.status=1';
}
$A = $this->prefix.'entry A';
$from 		= " FROM $A ";
$where 		= " WHERE A.warehouse_id IN($ws) ".($ret['where'] != ''?" AND ".$ret['where']:'') .$status;
$countSql 	= " SELECT COUNT(*)".$ret['sum']." $from $where";
$pageSql 	= " SELECT * $from $where ORDER BY A.id DESC";
$this->_pageList ( $countSql,$pageSql );
$this->display();
}
public function show()
{
$id = intval($_REQUEST['id']);
$model = D('Stockin');
$vo = $model->getPerm($id);
if($vo == false)
{
$this->error('您没有操作这个仓库的权限');
}
$this->setTitle('入库操作_'.$vo['id']);
$this->assign('vo',$vo);
$details = $model->getDetails($id);
$this->assign('details',$details);
$this->display();
}
public function insert()
{
$id = intval($_REQUEST['id']);
$model = D('Stockin');
$vo = $model->getPerm($id);
if($vo == false)
{
$this->ajaxReturn('','您没有操作这个仓库的权限',0);
}
$data['act_uid']		= $this->uid;
$data['act_time']	 	= time();
$data['status']			= 2;
$map['id'] 				= $id;
$map['status'] 			= 1;
$model = D('Entry');
$ret = $model->where($map)->save($data);
if (null == $ret)
{
$data['id'] = $id;
$this->ajaxReturn($data,'入库单状态已发生改变，不能再执行入库动作。',0);
}
$model->finish($id);
$this->ajaxReturn('','',1);
}
public function back()
{
$id = intval($_REQUEST['id']);
$model = D('Stockin');
$vo = $model->getPerm($id);
if($vo == false)
{
$this->error('您没有操作这个仓库的权限');
}
if($vo['status'] != 1)
{
$this->error('入库单的状态不正确，不能被退回');
}
if($vo['type'] != 1)
{
$this->error('入库单的类型不正确，不能被退回');
}
$data['act_uid']		= $this->uid;
$data['act_time']	 	= time();
$data['status']			= 4;
$map['id'] 				= $id;
$map['status'] 			= 1;
$model = D('Entry');
$ret = $model->where($map)->save($data);
$model->back($id);
$this->success('成功退回入库单');
}
}?>