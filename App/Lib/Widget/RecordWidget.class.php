<?php

class RecordWidget extends Widget 
{
public function render($data)
{
$module = $data['module'];
$recordId = $data['recordId'];
$B = C ( 'DB_PREFIX').'user';
$model = D('Record');
$data['vo'] = $model->query("SELECT A.*,B.name FROM __TABLE__ A LEFT JOIN $B B ON A.uid=B.id 
		WHERE A.module='$module' AND A.recordId=$recordId ORDER BY A.cTime DESC");
$content = $this->renderFile ( "Record",$data );
return $content;
}
}?>