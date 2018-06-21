<?php
namespace swiftphp\core\web\tags;

use swiftphp\core\utils\ObjectUtil;

class Radio extends TagBase
{
    private $dataSource=[];
    private $visible=true;
    private $checkedValue;
    private $class;
    private $name;
    private $valueField;
    private $textField;
    private $items=[];

    public function setDataSource($value)
    {
        $this->dataSource=$value;
    }
    public function setVisible($value)
    {
        $this->visible=$value;
    }
    public function setCheckedValue($value)
    {
        $this->checkedValue=$value;
    }
    public function setClass($value)
    {
        $this->class=$value;
    }
    public function setName($value)
    {
        $this->name=$value;
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
     * 绑定数据，当设置了$dataSource属性时，必须执行此方法，才能把数据映射到控件
     * @return void
     */
    public function bindData()
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
     * 覆盖父类getContent()方法,取得控件呈现给客户端的内容
     * @see lib/beans/bean#getContent()
     * @return string
     */
    public function getContent()
    {
        if(!empty($this->dataSource)){
            $this->bindData();
        }else{
            $this->loadClientItems();
        }

        if(!$this->visible){
            return "";
        }

        $attrs=$this->getAttributes();
        $attributes="";
        foreach(array_keys($attrs) as $name){
            if($name != "value"){
                $attributes .= " ".$name."=\"".$attrs[$name]."\"";
            }
        }

        $builder="";
        foreach($this->items as $item){
            $attStr=$attributes;
            if($item["value"]==$this->checkedValue){
                $attStr.=" checked=\"checked\"";
            }
            $builder .= "<input type=\"radio\" name=\"".$this->name."\" value=\"".$item["value"]."\"".$attStr." /><label>".$item["text"]."</label>";
        }
        if(isset($this->class) || $this->class !=""){
            $builder="<div class=\"".$this->class."\">".$builder."</div>";
        }
        return $builder;
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
            $value=$attrs["value"];
            //$value=str_replace("\'", "#####", $value);
            $value=str_replace("\\,", "######", $value);
            $value=str_replace("\\:", "#######", $value);
            $items_array=explode(",", $value);
            foreach ($items_array as $item_string){
                $item_string=str_replace("{","",$item_string);
                $item_string=str_replace("}","",$item_string);
                $item_array=explode(":",$item_string);
                if(count($item_array)==2){
                    $_name=$item_array[0];
                    $_value=$item_array[1];
                    //$_value=str_replace("#####", "'", $_value);
                    $_value=str_replace("######", ",", $_value);
                    $_value=str_replace("#######", ":", $_value);
                    $this->addItem($_name,$_value);
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
    public function addItem($value,$text,$index=-1)
    {
        if(count($this->items)==0){
            $item=[];
            $item["value"]=$value;
            $item["text"]=$text;
            $this->items[]=$item;
        }else if($index<0){
            $item=[];
            $item["value"]=$value;
            $item["text"]=$text;
            $this->items[]=$item;
        }else{
            $items=[];
            for($i=0;$i<count($this->items);$i++){
                if($i==$index){
                    $item=[];
                    $item["value"]=$value;
                    $item["text"]=$text;
                    $items[]=$item;
                }
                $items[]=$this->items[$i];
            }
            $this->items=$items;
        }
    }

}

