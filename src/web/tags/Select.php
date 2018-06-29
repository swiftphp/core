<?php
namespace swiftphp\core\web\tags;


/**
 * 下拉框列表控件
 * @author Tomix
 *
 */
class Select extends TagBase
{
    private $dataSource=[];
    private $valueField;
    private $textField;
    private $checkedValue;

    private $showTree=false;
    private $idField;
    private $pidField;
    private $rootId;
    private $level=-1;
    private $separator="&nbsp;&nbsp;&nbsp;&nbsp;";
    private $prefix="";

    private $showUndefine=false;
    private $undefineText="Undefine";
    private $undefineValue="";

	private $items=[];

	public function setDataSource($value)
	{
	    $this->dataSource = $value;
	}
	public function setValueField($value)
	{
	    $this->valueField = $value;
	}
	public function setTextField($value)
	{
	    $this->textField = $value;
	}
	public function setCheckedValue($value)
	{
	    $this->checkedValue = $value;
	}
	public function setShowTree($value)
	{
	    $this->showTree = $value;
	}
	public function setIdField($value)
	{
	    $this->idField = $value;
	}
	public function setPidField($value)
	{
	    $this->pidField = $value;
	}
	public function setRootId($value)
	{
	    $this->rootId = $value;
	}
	public function setLevel($value)
	{
	    $this->level = $value;
	}
	public function setSeparator($value)
	{
	    $this->separator = $value;
	}
	public function setPrefix($value)
	{
	    $this->prefix=$value;
	}
	public function setShowUndefine($value)
	{
	    $this->showUndefine = $value;
	}
	public function setUndefineText($value)
	{
	    $this->undefineText = $value;
	}
	public function setUndefineValue($value)
	{
	    $this->undefineValue = $value;
	}



	/**
	 * 覆盖父类getContent()方法,取得控件呈现给客户端的内容
	 * @see lib/beans/bean#getContent()
	 * @return string
	 */
	public function getContent(&$outputParams=[])
	{
		if(is_array($this->dataSource) && count($this->dataSource)>0){
			$this->bindData();
		}

		$clientItems=$this->loadClientItems();

		$attrs=$this->getAttributes();
		$attributes="";
		foreach ($attrs as $name=>$val){
		    $attributes .= " ".$name."=\"".$val."\"";
		}
		$builder="";
		$builder .= "<select".$attributes.">\r\n";
		if($this->showUndefine){
			$option="<option value=\"{0}\">{1}</option>";
			if($this->undefineValue==$this->checkedValue){
				$option="<option selected=\"selected\" value=\"{0}\">{1}</option>";
			}
			$option=str_replace("{0}",$this->undefineValue,$option);
			$option=str_replace("{1}",$this->undefineText,$option);
			$builder.=$option."\r\n";

		}
		if(count($clientItems)>0){
			foreach ($clientItems as $item){
				$option=$item["option"];
				if($item["value"]==$this->checkedValue){
					$option=str_replace("<option","<option selected=\"selected\"",$option);
				}
				$builder.=$option;
			}
		}else{
			foreach($this->items as $item){
				$option="<option value=\"{0}\">{1}</option>";
				if($item["value"]==$this->checkedValue){
					$option="<option selected=\"selected\" value=\"{0}\">{1}</option>";
				}
				$option=str_replace("{0}",$item["value"],$option);
				$option=str_replace("{1}",$item["text"],$option);
				$builder.=$option."\r\n";
			}
		}
		$builder.="</select>";
		return $builder;
	}

	/**
	 * 绑定数据，当设置了$dataSource属性时，将自动执行此方法，把数据映射到控件
	 * @return void
	 */
	private function bindData()
	{
	    $this->items=[];
	    if(count($this->dataSource)==count($this->dataSource,COUNT_RECURSIVE)){
	        foreach(array_keys($this->dataSource) as $key){
	            $item=["value"=>$key,"text"=>$this->dataSource[$key]];
	            $this->items[]=$item;
	        }
	    }else{
	        if($this->showTree){
	            $this->buildTreeItems();
	        }else{
	            foreach($this->dataSource as $row){
	                $item=["value"=>$row[$this->valueField],"text"=>$row[$this->textField]];
	                $this->items[]=$item;
	            }
	        }
	    }
	}

	/**
	 * 创建树装结构的选项集
	 * @return void
	 */
	private function buildTreeItems()
	{
	    foreach($this->dataSource as $arr){
	        $rootArray=[];
	        if(isset($this->rootId)){
	            $rootArray[0]=$this->rootId;
	        }else{
	            $rootArray[0]="";
	            $rootArray[1]="0";
	            $rootArray[2]=null;
	        }

	        if(in_array($arr[$this->pidField],$rootArray)){
	            //取得根的下一级子数据
	            $item=[];
	            $item["value"]=$arr[$this->valueField];
	            $item["text"]=$this->prefix.$arr[$this->textField];
	            $this->items[]=$item;

	            //取所有子選項
	            $this->buildChildItems($arr[$this->idField],1);
	        }
	    }
	}

	/**
	 * 创建树状结构时创建子选项集
	 * @param $pid
	 * @param $level
	 * @return unknown_type
	 */
	private function buildChildItems($pid,$level)
	{
	    if($this->level<0){
	        $this->level=99999999;
	    }
        if($level > $this->level){
            return;
        }

        //文本缩进符号
        $sep="";
        for($i=0;$i<$level;$i++){
            $sep .= $this->separator;
        }
        $sep.=$this->prefix;
        foreach($this->dataSource as $arr){
            if($arr[$this->pidField]==$pid){
                $item=[];
                $item["value"]=$arr[$this->valueField];
                $item["text"]=$sep.$arr[$this->textField];
                $this->items[]=$item;

                //取得所有子选项
                $this->buildChildItems($arr[$this->idField],$level+1);
            }
        }
	}

	/**
	 * 加载客户端口定义的选项值，如果客户端有定义，则只呈现客户端的选项
	 * 客户端选项标识如:value="{0,A}{1,B}"
	 * @return void
	 */
	private function loadClientItems()
	{
		$returnItems=[];
		$pattern="/<option[^<>\/]{0,}>[^<>]{0,}<\/option>|<option[^<>\/]{0,}\/>/";
		$matches =[];
		preg_match_all($pattern,$this->getInnerHtml(),$matches,PREG_SET_ORDER);
		foreach($matches as $match){
			$item=$match[0];
			$item=str_replace(" selected=\"selected\"","",$item);
			$pattern="/value=\"([^\"]{0,})\"/";
			$values=[];
			preg_match($pattern,$item,$values);
    		$value = $values[1];
			$arr=["value"=>$value,"option"=>$item];
			$returnItems[]=$arr;
		}
		return $returnItems;
	}
}