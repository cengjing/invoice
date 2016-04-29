<?php
	function  bool2text($val){
		return ($val==1)?'是':'否';
	}
	function dhtml($string) {
		if (is_array ( $string )) {
			foreach ( $string as $key => $val ) {
				$string [$key] = dhtml ( $val );
			}
		} else {
			$string = str_replace ( array ('"', '\'', '<', '>', "\t", "\r", '{', '}' ), array ('&quot;', '&#39;', '&lt;', '&gt;', '&nbsp;&nbsp;', '', '&#123;', '&#125;' ), $string );
		}
		return $string;
	}
	function getSex($sex) {
		return ($sex!=''?(($sex==1)?'男':'女'):'');
	}
	function tree_to_array($list, $level = 0, &$tree, $separator = '├-', $blank=true) {
		foreach ( $list as $val ) {
			if($blank)
				$tmp_str = str_repeat ( "&nbsp;", $level * 2 );
			if ($level > 0) {
				$tmp_str .= $separator;
			}
			$val ['title'] = $tmp_str . $val ['title'];
			$val ['level'] = $level;
			if (! array_key_exists ( '_child', $val )) {
				array_push ( $tree, $val );
			} else {
				$tmp_ary = $val ['_child'];
				unset ( $val ['_child'] );
				$val['child'] = true;
				array_push ( $tree, $val );
				tree_to_array ( $tmp_ary, $level + 1, $tree, $separator ,$blank);
			}
		}
		return;
	}
	function is_mychild($list, $id, $uid) {
		if ($id == $uid)return true;
		$child = get_mychild ( $list, $id );
		if (count ( $child ) > 1)
			return in_array ( $uid, $child );
		else
			return false;
	}
	function get_mychild($list, $id) {
		$child = array ($id );
		foreach ( $list as $key => $val ) {
			if ($id == $val ['id']) break;
		}
		$j = count ( $list );
		while ( $key <= $j ) {
			++ $key;
			$t_id = $list [$key] ['pid'];
			if (in_array ( $t_id, $child ))
				array_push ( $child, $list [$key] ['id'] );
			else
				break;
		}
		return $child;
	}
	function getCatalogTitle($id) {
		if (! $id)return;
		$vo = S('getCatalogTitle');
		if (! $vo) {
			$vo = M ( "Catalog" )->select();
			S ( 'getCatalogTitle', $vo, 3600 );
		}
		foreach ( $vo as $v ) {
			if ($v ["id"] == $id)return $v ["title"];
		}
	}
	function txtStatus($num) {
		return ($num == 1)?'正常':'关闭';
	}

	function getUserInfoById($Ids, $field = 'name') {
		if(empty($Ids)) return;
		$vo = S('UserInfo');
		if (! $vo) 
		{
			$vo = M('User')->field('id,name,username,sex,department_id')->select();
			foreach ( $vo as $v ) {
				$tmp [$v ['id']] = $v;
			}
			unset ( $vo );
			$vo = $tmp;
			S ( 'UserInfo', $vo, 3600);
		}
		$Ids = explode ( ',', $Ids );
		$result = '';
		foreach ( $Ids as $Id ) 
		{
			if($Id != '')
				$result .= ($result == '' ? '' : ',').$vo[$Id][$field];
		}
		return $result;
	}
	function getUserDepartmentById($Ids){
		if ($Ids == '') return;
		$vo = S('DepartmentInfo');
		if (!$vo){
			$vo = M('Department')->field('id,title')->select();
			foreach ( $vo as $v ) {
				$tmp [$v ['id']] = $v;
			}
			unset($vo);
			$vo = $tmp;
			S('DepartmentInfo', $vo, 3600);
		}
		$Ids = explode ( ',', $Ids );
		$result = '';
		foreach ( $Ids as $Id ) 
		{
			if($Id != '')
				$result .= ($result == '' ? '' : ',').$vo[$Id]['title'];
		}
		return $result;
	}
	function getReadTime($time) {
		return ($time == 0)?"未读":(date ( "Y-m-d H:i", $time ));
	}
	function getTime($time) {
		return ($time == 0 || $time == '') ? '' : (date ( "Y-m-d", $time ));
	}	
	//检查并创建多级目录
	function checkDir($path){
		$pathArray = explode('/',$path);
		$nowPath = '';
		array_pop($pathArray);
		foreach ($pathArray as $key=>$value){
			if ( ''==$value ){
				unset($pathArray[$key]);
			}else{
				if ( $key == 0 )
					$nowPath .= $value;
				else
					$nowPath .= '/'.$value;
				if ( !is_dir($nowPath) ){
					if ( !mkdir($nowPath, 0777) ) return false;
				}
			}
		}
		return true;
	}
	function getUserFace($uid, $size='small', $sex=1){
		if($size == 'small'){
			$file = "/Public/uploads/user/$uid/face_s.jpg";
		}else {
			$file = "/Public/uploads/user/$uid/face_l.jpg";
		}
		if(!file_exists('.'.$file)) {
			if($sex==1){
				return __ROOT__."/Public/Theme/images/avatar2.jpg";
			}else{
				return __ROOT__."/Public/Theme/images/avatar1.jpg";
			}
		}
		return __ROOT__.$file;
	}
	function byte_format($size, $dec = 2) {
		$a = array ("B", "KB", "MB", "GB", "TB", "PB" );
		$pos = 0;
		while ( $size >= 1024 ) {
			$size /= 1024;
			$pos ++;
		}
		return round ( $size, $dec ) . " " . $a [$pos];
	}
	function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0) {
		$tree = array ();
		if (is_array ( $list )) {
			// 创建基于主键的数组引用
			$refer = array ();
			foreach ( $list as $key => $data ) {
				$refer [$data [$pk]] = & $list [$key];
			}
			foreach ( $list as $key => $data ) {
				// 判断是否存在parent
				$parentId = $data [$pid];
				if ($root == $parentId) {
					$tree [] = & $list [$key];
				} else {
					if (isset ( $refer [$parentId] )) {
						$parent = & $refer [$parentId];
						$parent [$child] [] = & $list [$key];
					}
				}
			}
		}
		return $tree;
	}
	function list_sort_by($list,$field, $sortby='asc') {
	   if(is_array($list)){
	       $refer = $resultSet = array();
	       foreach ($list as $i => $data)
	           $refer[$i] = &$data[$field];
	       switch ($sortby) {
	           case 'asc': // 正向排序
	                asort($refer);
	                break;
	           case 'desc':// 逆向排序
	                arsort($refer);
	                break;
	           case 'nat': // 自然排序
	                natcasesort($refer);
	                break;
	       }
	       foreach ( $refer as $key=> $val)
	           $resultSet[] = &$list[$key];
	       return $resultSet;
	   }
	   return false;
	}
	function list_search($list, $condition) {
		if (is_string ( $condition ))
			parse_str ( $condition, $condition );
			// 返回的结果集合
		$resultSet = array ();
		foreach ( $list as $key => $data ) {
			$find = false;
			foreach ( $condition as $field => $value ) {
				if (isset ( $data [$field] )) {
					if (0 === strpos ( $value, '/' )) {
						$find = preg_match ( $value, $data [$field] );
					} elseif ($data [$field] == $value) {
						$find = true;
					}
				}
			}
			if ($find)
				$resultSet [] = &$list [$key];
		}
		return $resultSet;
	}
	function getStatus($status) {
		if($status == 1){
			$status = '已保存';
		}elseif ($status == 2){
			$status = '待审核';
		}elseif ($status == 3){
			$status = '已审核';
		}elseif ($status == 4){
			$status = '退回审核';
		}elseif ($status == 5){
			$status = '完成';
		}elseif ($status == 6){
			$status = '删除';
		}else{
			$status = '未知';
		}
		return "<span class='status'>".$status.'</span>';
	}
	function getDeliveryStatus($status) {
		if($status == 1){
			$status = '准备发货';
		}elseif($status == 3){
			$status = '删除';
		}elseif($status == 2){
			$status = '已发货';
		}else{
			$status = '申请退回';
		}
		return "<span class='status'>".$status.'</span>';
	}
	function getDeliveryType($type)
	{
		if($type == 1){
			$type = '销售发货';
		}elseif($type == 2){
			$type = '调拨发货';
		}elseif($type == 3){
			$type = '盘亏发货';
		}
		return $type;
	}
	function getEntryStatus($status) 
	{
		$arr = array(1=>'准备入库', 2=>'完成入库', 3=>'删除', 4=>'退回');
		$status = isset($arr[$status])?$arr[$status]:'其它应收款';
		return "<span class='status'>".$status.'</span>';
	}
	function getARType($type)
	{
		$arr = array(1=>'销售发票', 2=>'销售退货', 3=>'预收款', 4=>'暂存款', 5=>'其它应收款');
		return isset($arr[$type])?$arr[$type]:'其它应收款';
	}
	function getAPType($type)
	{
		$arr = array(1=>'采购发票', 2=>'销售退货', 3=>'预付款', 4=>'暂存款');
		return isset($arr[$type])?$arr[$type]:'其它应付款';
	}
	function getWarehouseInfo( $id ) {
		$vo = S('warehouseName');
		if(!$vo){
			$resultSet = M('WarehouseName')->field('id,name,dbfield')->select();
			foreach ( $resultSet as $val)
	           $vo[$val['id']] = $val;
			S('warehouseName', $vo, 3600);
		}
		return $vo[$id];
	}
	function getWarehouseName($id)
	{
		$vo = getWarehouseInfo($id);
		return $vo['name'];
	}
	function getVenderName($id) {
		$vo = S('venderName');
		if(!$vo){
			$resultSet = M('Vender')->field('id,company')->select();
			foreach ( $resultSet as $val)
	           $vo[$val['id']] = $val;
			S('venderName',$vo,3600);
		}
		return $vo[$id]['company'];
	}
	function array2text($arr) {
		$arr = array_unique($arr);
		$result = '';
		foreach ($arr as $v){
			if($v != ''){
				if($result == ''){
					$result = "'$v'";
				}else{
					$result .= ",'$v'";
				}
			}
		}
		return $result;
	}
	function array2key($vo, $field){
		$result = array();
		foreach ($vo as $v){
			$result[$v[$field]] = $v;
		}
		return $result;
	}
	function keyGen() {
		return str_replace ( '-', '', substr ( com_create_guid (), 1, - 1 ) );
	}
	function getInvoiceStatus($status){
		if($status == 1){
			$status = '提交开票';
		}elseif ($status == 2){
			$status = '已开票';
		}elseif ($status == 3){
			$status = '已删除';
		}elseif ($status == 4){
			$status = '已收款';
		}else{
			$status = '未知';
		}
		return "<span class='status'>".$status.'</span>';
	}
	function getPurchaseInvoiceStatus($status)
	{
		if ($status == 3){
			$status = '已开票';
		}elseif ($status == 5){
			$status = '完成';
		}else{
			$status = '删除';
		}
		return "<span class='status'>".$status.'</span>';
	}
	function getInvoiceType($type){
		if($type == 1){
			$type = "<span style='color:blue;'>蓝字发票</span>";
		}elseif ($type == 2){
			$type = "<span style='color:red;'>红字发票</span>";
		}else{
			$type = '未知';
		}
		return $type;
	}
	function getReceiptCategory($id){
		if( $id == 1 ){
			$type = '预收款';
		}elseif ( $id ==2 ){
			$type = '暂存款';
		}elseif ( $id ==3 ){
			$type = '发票收款';
		}elseif ( $id == 4 ){
			$type = '退货收款';
		}elseif ( $id == 5 ){
			$type = '其它收款';
		}
		return $type;
	}
	function getPaymentCategory($id)
	{
		if( $id == 1 ){
			$type = '预付款';
		}elseif ( $id ==2 ){
			$type = '暂存款';
		}elseif ( $id ==3 ){
			$type = '采购付款';
		}elseif ( $id == 4 ){
			$type = '退货收款';
		}elseif ( $id == 5 ){
			$type = '订单退款';
		}else{
			$type = '其它付款';
		}
		return $type;
	}
	function getEntryType($id){
		if( $id == 1 ){
			$type = '采购入库';
		}elseif ( $id ==2 ){
			$type = '调拨入库';
		}elseif ( $id == 3 ){
			$type = '退货入库';
		}elseif ( $id == 4 ){
			$type = '盘盈入库';
		}
		return $type;
	}
	function getPackListLevel($level){
		return ($level == 1) ? '单级' : '多级';
	}
	function getContactTitleById($id){
		$vo = M('Contacts')->field('id,name')->getById($id);
		printf("<a class='contact_info' cid='%s' href='__APP__/contacts/show/id/%s'>%s</a>", $vo['id'], $vo['id'], $vo['name']);
	}
	function getCompanyTitleById($id){
		$vo = M('Accounts')->field('id,company')->getById($id);
		printf("<a class='account_info' cid='%s' href='__APP__/accounts/show/id/%s'>%s</a>", $vo['id'], $vo['id'], $vo['company']);
	}
	function getPicklistValue($id){
		if(intval($id)<=0)return;
		$vo = M('PicklistDetails')->field('title')->where("id=$id")->find();
		return $vo['title'];
	}
	function getVenderTitleById($id,$url=true){
		$vo = M('Vender')->field('id,company')->getById($id);
		if($url){
			printf("<a href='__APP__/vender/show/id/%s'>%s</a>", $vo['id'], $vo['company']);
		}else {
			return $vo['company'];
		}
	}
	function id2action($id,$module)
	{
		printf("<a href='__APP__/%s/edit/id/%s'>修改</a>", $module, $id);
	}
	function getNetwork($id){
		$vo = M('Network')->field('title')->where("id=$id")->find();
		return $vo['title'];
	}
	function getProductInfo($id)
	{
		$vo = M('Products')->where(array('id'=>$id))->find();
		return $vo;
	}
	function idEncrypt($str)
	{
		import ( 'ORG.Crypt.Base64' );
		return base64_encode(Base64::encrypt($str, C('CRYPT_KEY')));
	}
	function idDecrypt($str)
	{
		import ( 'ORG.Crypt.Base64' );
		return Base64::decrypt(base64_decode($str), C('CRYPT_KEY'));
	}
	function getSalesorderUrl($id){
		printf("<a href='__APP__/salesorder/show/id/%s'>$id</a>", $id); 
	}
	function time2unix($time){
		$date = explode('-', $time);
		$ret = mktime(0,0,0,$date[1],$date[2],$date[0]);
		if($ret == '') $ret = null;
		return $ret;
	}
	/**
	 * 去一个二维数组中的每个数组的固定的键知道的值来形成一个新的一维数组
	 * @param $pArray 一个二维数组
	 * @param $pKey 数组的键的名称
	 * @return 返回新的一维数组
	 */
	function getSubByKey($pArray, $pKey="", $pCondition=""){
	    $result = array();
		foreach($pArray as $temp_array){
	        if(is_object($temp_array)){
	        	$temp_array = (array) $temp_array;
	        }
			if((""!=$pCondition && $temp_array[$pCondition[0]]==$pCondition[1]) || ""==$pCondition) {
	        	$result[] = (""==$pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : "";
	    	}
	    }
		return $result;
	}
	function getConfigValue($name)
	{
		$vo = S('Config');
		if(!$vo)
		{
			$vo = M('Config')->select();
			foreach ($vo as $v){
				$tmp[$v['name']] = $v['value'];
				if($v['name'] == 'assistant_module'){
					$ids = explode(',', $v['value']);
					$arr = M('Modules')->where(array('id'=>array('IN',$ids)))->field('module')->select();
					$tmp[$v['name']] = $arr;
				}
			}
			S('Config', $tmp);
			unset($vo);
			$vo = $tmp;
		}
		return $vo[$name];
	}
	function getPriceHl($hl)
	{
		if($hl == 1) $hl = "上调";
		if($hl == 2) $hl = "下调";
		return $hl;
	}
	function getFeedbackStatus($id)
	{
		return $id == 1?'发布':'删除';
	}
	function formatCurrency($amount)
	{
		return number_format($amount, 2, ".", ",");
	}
	function changeMinus($value)
	{
		$value = formatCurrency($value);
		return '-'.$value;
	}
	function safeHtml($text)
	{
	    $text =  trim($text);
        //完全过滤注释
        $text = preg_replace('/<!--?.*-->/','',$text);
        //完全过滤动态代码
        $text =  preg_replace('/<\?|\?'.'>/','',$text);
        //完全过滤js
        $text = preg_replace('/<script?.*\/script>/','',$text);

        return $text;
	}
	// 自动转换字符集 支持数组转换
	function auto_charset($fContents, $from='gbk', $to='utf-8') {
		$from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
		$to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
		if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
			//如果编码相同或者非字符串标量则不转换
			return $fContents;
		}
		if (is_string($fContents)) {
			if (function_exists('mb_convert_encoding')) {
				return mb_convert_encoding($fContents, $to, $from);
			} elseif (function_exists('iconv')) {
				return iconv($from, $to, $fContents);
			} else {
				return $fContents;
			}
		} elseif (is_array($fContents)) {
			foreach ($fContents as $key => $val) {
				$_key = auto_charset($key, $from, $to);
				$fContents[$_key] = auto_charset($val, $from, $to);
				if ($key != $_key)
					unset($fContents[$key]);
			}
			return $fContents;
		}
		else {
			return $fContents;
		}
	}
	function is_utf8($string) {
		return preg_match('%^(?:
				[\x09\x0A\x0D\x20-\x7E]            # ASCII
				| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
				|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
				| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
				|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
				|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
				| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
				|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
		)*$%xs', $string);
	}
?>