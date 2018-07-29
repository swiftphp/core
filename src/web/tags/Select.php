<?php
namespace swiftphp\core\web\tags;


use swiftphp\core\utils\Convert;

/**
 * 下拉框列表控件
 * @author Tomix
 *
 */
class Select extends AbstractListItemBase
{
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
		    if($this->showTree){
		        $this->buildTreeItems();
		    }else{
		        $this->items=$this->buildDataItems();
		    }
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
			    $value=$item["value"];
			    $text=$item["text"];
				$option="<option value=\"{0}\">{1}</option>";
				if($value==$this->checkedValue){
					$option="<option selected=\"selected\" value=\"{0}\">{1}</option>";
				}
				$option=str_replace("{0}",$value,$option);
				$option=str_replace("{1}",$text,$option);
				$builder.=$option."\r\n";
			}
		}
		$builder.="</select>";
		return $builder;
	}

	/**
	 * 创建树装结构的选项集
	 * @return void
	 */
	private function buildTreeItems()
	{
	    foreach($this->dataSource as $obj){
	        $rootArray=[];
	        if(isset($this->rootId)){
	            $rootArray[0]=$this->rootId;
	        }else{
	            $rootArray[0]="";
	            $rootArray[1]="0";
	            $rootArray[2]=null;
	        }

	        $pid=Convert::getFieldValue($obj, $this->pidField,true);
	        if(in_array($pid,$rootArray)){
	            //取得根的下一级子数据
	            $item=[];
	            $item["value"]=Convert::getFieldValue($obj, $this->valueField,true);
	            $item["text"]=$this->prefix.Convert::getFieldValue($obj, $this->textField,true);
	            $this->items[]=$item;

	            //取所有子選項
	            $id=Convert::getFieldValue($obj, $this->idField,true);
	            $this->buildChildItems($id,1);
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
        foreach($this->dataSource as $obj){
            $pidValue=Convert::getFieldValue($obj, $this->pidField,true);
            if($pidValue==$pid){
                $item=[];
                $item["value"]=Convert::getFieldValue($obj, $this->valueField,true);
                $item["text"]=$sep.Convert::getFieldValue($obj, $this->textField,true);
                $this->items[]=$item;

                //取得所有子选项
                $idValue=Convert::getFieldValue($obj, $this->idField,true);
                $this->buildChildItems($idValue,$level+1);
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