<?php
namespace swiftphp\core\web\tags;


use swiftphp\core\utils\ObjectUtil;

/**
 * 复选框列表标签
 * @author Tomix
 *
 */
class Checkbox extends TagBase
{
	/**
	 * 单选列表框名称
	 * @var string
	 */
	private $name;

	/**
	 * 默认选中值
	 * @var array
	 */
	private $checkedValues;

	/**
	 * 样式
	 * @var string
	 */
	private $class;

	/**
	 * 数据源(如果数据源为二维，必须设置$valueField,$titleField属性)
	 * @var array
	 */
	private $dataSource=[];

	/**
	 * 值字段
	 * @var string
	 */
	private $valueField;

	/**
	 * 文本字段
	 * @var string
	 */
	private $textField;

	/**
	 * 列表选项集合(二维:,value,text集合)
	 * @var array
	 */
	private $items=[];

    public function setName($value)
    {
        $this->name=$value;
    }
    public function setCheckedValues($value)
    {
        $this->checkedValues=$value;
    }
    public function setClass($value)
    {
        $this->class=$value;
    }
    public function setDataSource($value)
    {
        $this->dataSource=$value;
    }
    public function setValueField($value)
    {
        $this->valueField=$value;
    }
    public function setTextField($value)
    {
        $this->textField=$value;
    }


	/**
	 * 获取标签渲染后的内容
	 * {@inheritDoc}
	 * @see \swiftphp\core\web\tags\TagBase::getContent()
	 */
    public function getContent(&$outputParams=[])
	{
	    if(is_array($this->dataSource) && count($this->dataSource)>0){
	        $this->bindData();
	    }
	    $this->loadClientItems();

        $attrs=$this->getAttributes();
        $attributes="";
        foreach ($attrs as $key=>$val){
            $attributes .= " ".$key."=\"".$val."\"";
        }

        $builder="";
        if(!is_array($this->checkedValues)){
            $this->checkedValues=explode(",",$this->checkedValues);
        }
        foreach($this->items as $item){
            $attStr=$attributes;
            if(in_array($item["value"],$this->checkedValues)){
                $attStr.=" checked=\"checked\"";

            }
            $builder .= "<input type=\"checkbox\" name=\"".$this->name."[]\" value=\"".$item["value"]."\" ".$attStr." /><label>".$item["text"]."</label>";
        }
        if(isset($this->class) || $this->class !=""){
            $builder="<div class=\"".$this->class."\">".$builder."</div>";
        }

        return $builder;
	}

	/**
	 * 绑定数据，当设置了$dataSource属性时，必须执行此方法，才能把数据映射到控件
	 * @return void
	 */
	private function bindData()
	{
	    if(is_array($this->dataSource) && count($this->dataSource)>0){
	        $this->items=[];
	        if(count($this->dataSource)==count($this->dataSource,COUNT_RECURSIVE)){
	            //键值对数组或对象数组
	            foreach ($this->dataSource as $key => $value){
	                if(is_object($value)){
	                    $_value=ObjectUtil::getPropertyValue($value, $this->valueField,true);
	                    $_text=ObjectUtil::getPropertyValue($value, $this->textField,true);
	                    $this->items[]=["value"=>$_value,"text"=>$_text];
	                }else{
	                    $this->items[]=["value"=>$key,"text"=>$value];
	                }
	            }
	        }else{
	            //二维数组
	            foreach($this->dataSource as $row){
	                $this->items[]=["value"=>$row[$this->valueField],"text"=>$row[$this->textField]];
	            }
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
	    $attrs=$this->getAttributes();
		if(isset($attrs["value"]) && $attrs["value"] != ""){
			$this->items=[];
			$items_array=explode("}",$attrs["value"]);
			foreach($items_array as $item_string){
				$item_string=str_replace("{","",$item_string);
				$item_string=str_replace("}","",$item_string);
				$item_array=explode(",",$item_string);
				if(count($item_array)==2){
					$this->addItem($item_array[0],$item_array[1]);
				}
			}
		}
	}

	/**
	 * 添加选项
	 * @param $value string 选项值
	 * @param $text string 选项文本
	 * @param $index int 插入位置
	 * @return void
	 */
	private function addItem($value,$text)
	{
	    $item=[];
	    $item["value"]=$value;
	    $item["text"]=$text;
	    $this->items[]=$item;
	}
}