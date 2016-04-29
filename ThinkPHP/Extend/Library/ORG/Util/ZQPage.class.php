<?php

class ZQPage extends Think {
    // 模式设置,0为简单上下首尾,1为增加数字，2增加总页数和总数据，3再增加快进
    public $type;
	// 起始行数
    public $firstRow;
    // 列表每页显示行数
    public $listRows;
    // 页数跳转时要带的参数
    public $parameter;
    //样式
    public $style;
	// 语言设置 0为中文,1为英文,2为中文加符号,3为英文加符号
    protected $language;
    //css类名
    protected $classNames = array("default","baidu","yahoo","digg","orange","sabrosus","scott","quotes","yellow");
    // 分页总页面数
    protected $totalPages;
    // 总行数
    protected $totalRows;
    // 当前页数
    protected $nowPage;
    // 分页的栏的总页数
    protected $coolPages;
    // 分页栏每页显示的页数
    protected $rollPage;
	// 分页显示定制
	protected $config = array('rows_info'=>'总条数：','pages_info'=>'当前页：','prev'=>'上一页','next'=>'下一页','up'=>'快退','down'=>'快进','first'=>'首页','last'=>'尾页','theme'=>'%rows_info% %totalRows% %pages_info% %nowPage%/%totalPage% &nbsp; %first% %prePage% %upPage% %linkPage% %downPage% %nextPage% %end%');
	
	//分页标记
	protected $varPage;
	
	public function __construct($totalRows,$listRows,$style=0,$language=0,$type=3,$parameter=''){
		$this->language = $language;
		$this->type = $type;
		$this->style = $style;
		$this->totalRows = $totalRows;
        $this->parameter = $parameter;
        $this->rollPage = C('PAGE_ROLLPAGE');
        $this->listRows = !empty($listRows)?$listRows:C('PAGE_LISTROWS');
        $this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
        $this->coolPages  = ceil($this->totalPages/$this->rollPage);
        $this->varPage = C('VAR_PAGE') ? C('VAR_PAGE') : 'p' ;
        $this->nowPage  = !empty($_GET[$this->varPage])?$_GET[$this->varPage]:1;
        if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->listRows*($this->nowPage-1);
        
	}
	
	public function getPage(){
		return $this->nowPage;
	}

	public function setConfig($name,$value) {
		if(isset($this->config[$name])) {
			$this->config[$name]    =   $value;
		}
	}

	public function show(){
		if(0 == $this->totalRows) return '';
		//
		// style
		if(!is_int($this->style))$this->style=0;
		if($this->language==1){
			$this->config['prev']='Front';
			$this->config['up']='Rewind';
			$this->config['next']='Next';
			$this->config['down']='Fast Forward';
			$this->config['first']='First';
			$this->config['last']='Last';
			$this->config['rows_info']='Total:';
			$this->config['pages_info']='Page:';
		}
		if($this->language>1){
			$this->config['prev']='&lt;';
			$this->config['next']='&gt;';
			$this->config['up']='&lt;&lt;';
			$this->config['down']='&gt;&gt;';
			$this->config['first']='|&lt;';
			$this->config['last']='&gt;|';
		}
		if($this->language==2){
			$this->config['rows_info']='总条数:';
			$this->config['pages_info']='当前页:';
		}
		if($this->language==3){
			$this->config['rows_info']='Total:';
			$this->config['pages_info']='Page:';
		}
		$style=$this->classNames[$this->style];
		$bd=array('','');
		if($this->style==1) $bd=array('[',']');
		//接收参数
		$p = $this->varPage;
		$nowColPage      = ceil($this->nowPage/$this->rollPage);
		$url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
		$parse = parse_url($url);
		if(isset($parse['query'])) {
			parse_str($parse['query'],$params);
			unset($params[$p]);
			$url   =  $parse['path'].'?'.http_build_query($params);
		}
		//计算前后
		$upRow   = $this->nowPage-1;
		$downRow = $this->nowPage+1;
		if ($upRow>0){
			$upPage="<a href='".$url."&".$p."=$upRow'>".$this->config['prev']."</a>&nbsp;";
		}elseif($this->style>1){
			$upPage="<span class='disabled'>".$this->config['prev']."</span>&nbsp;";
		}else{
			$upPage="";
		}

		if ($downRow <= $this->totalPages){
			$downPage="&nbsp;<a href='".$url."&".$p."=$downRow'>".$this->config['next']."</a>&nbsp;";
		}elseif($this->style>1){
			$downPage="&nbsp;<span class='disabled'>".$this->config['next']."</span>";
		}else{
			$downPage="";
		}

		//计算首尾
		if($nowColPage == 1){
			$theFirst = "";
			$prePage = "";
			if($this->style>1){
				$theFirst="&nbsp;&nbsp;&nbsp;&nbsp;<span class='disabled'>".$this->config['first']."</span>";
			}
		}else{
			$preRow =  $this->nowPage-$this->rollPage;
			$prePage = "<a href='".$url."&".$p."=$preRow' >".$this->config['up']."</a>";
			$theFirst = "&nbsp;&nbsp;&nbsp;&nbsp;<a href='".$url."&".$p."=1' >".$this->config['first']."</a>";
		}
		if($nowColPage == $this->ColPages){
			$nextPage = "";
			$theEnd="";
			if($this->style>1){
				//2012-08-01修改，增加了尾页的超链接
				//$theEnd="<span class='disabled'>".$this->config['last']."</span>";
				$theEndRow = $this->totalPages;
				$theEnd = "<a href='".$url."&".$p."=$theEndRow' >".$this->config['last']."</a>";
			}
		}else{
			$nextRow = $this->nowPage+$this->rollPage;
			$theEndRow = $this->totalPages;
			$nextPage = "<a href='".$url."&".$p."=$nextRow' >".$this->config['down']."</a>";
			$theEnd = "<a href='".$url."&".$p."=$theEndRow' >".$this->config['last']."</a>";
		}

		//数字
		$linkPage = "";
		for($i=1;$i<=$this->rollPage;$i++){
			$page=($nowColPage-1)*$this->rollPage+$i;
			if($page!=$this->nowPage){
				if($page<=$this->totalPages){
					$linkPage .= "&nbsp;<a href='".$url."&".$p."=$page'>".$bd[0]."&nbsp;".$page."&nbsp;".$bd[1]."</a>&nbsp;";
				}else{
					break;
				}
			}else{
				if($this->totalPages != 1){
					$linkPage .= "&nbsp;<span class='current'>".$page."</span>&nbsp;";
				}
			}
		}
		//buildHtml
		if($this->type==3){
			$pageHtml = '<div class="'.$style.'">'.'%rows_info% %totalRows% %pages_info% %nowPage%/%totalPage% &nbsp; %first% %prePage% %upPage% %linkPage% %downPage% %nextPage% %end%'.'</div>';
		}elseif($this->type==2){
			$pageHtml = '<div class="'.$style.'">'.'%rows_info% %totalRows% %pages_info% %nowPage%/%totalPage% &nbsp; %first% %upPage% %linkPage% %downPage% %end%'.'</div>';
		}elseif($this->type==1){
			$pageHtml = '<div class="'.$style.'">'.'%first% %upPage% %linkPage% %downPage% %end%'.'</div>';
		}elseif($this->type==0){
			$pageHtml = '<div class="'.$style.'">'.'%first% %upPage%%downPage% %end%'.'</div>';
		}else{
			$pageHtml = '<div class="'.$style.'">'.$this->config['theme'].'</div>';
		}
		$pageStr	 =	 str_replace(
			array('%rows_info%','%pages_info%','%nowPage%','%totalRows%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%'),
			array($this->config['rows_info'],$this->config['pages_info'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd),$pageHtml);
		return $pageStr;
	}
}
?>