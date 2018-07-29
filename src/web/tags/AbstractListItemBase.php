<?php
namespace swiftphp\core\web\tags;

use swiftphp\core\utils\Convert;

/**
 *
 * @author Tomix
 *
 */
abstract class AbstractListItemBase extends TagBase
{

	/**
	 * 数据源(如果数据源为二维，必须设置$valueField,$titleField属性)
	 * @var array
	 */
	protected $dataSource=[];

	/**
	 * 值字段
	 * @var string
	 */
	protected $valueField;

	/**
	 * 文本字段
	 * @var string
	 */
	protected $textField;

	/**
	 * 设置数据源
	 * @param array $value
	 */
	public function setDataSource($value)
	{
	    $this->dataSource=$value;
	}

	/**
	 * 值字段名
	 * @param string $value
	 */
	public function setValueField($value)
	{
	    $this->valueField=$value;
	}

	/**
	 * 文本字段名
	 * @param string $value
	 */
	public function setTextField($value)
	{
	    $this->textField=$value;
	}

	/**
	 * 创建列表项
	 * @return array
	 */
	protected function buildDataItems()
	{
        if(!is_array($this->dataSource) || count($this->dataSource)==0){
            return [];
        }

        $items=[];
        $firstItem=$this->dataSource[array_keys($this->dataSource)[0]];

        if(is_array($firstItem)){
            //一维键值对数组
            foreach ($this->dataSource as $key => $value){
                //$items[$key]=$value;
                $items[]=["value"=>$key,"text"=>$value];
            }
        }else{
            //二维表数组
            foreach ($this->dataSource as $item){
                $value=Convert::getFieldValue($item, $this->valueField, true);
                $text=Convert::getFieldValue($item, $this->textField, true);
                //$items[$value]=$text;
                $items[]=["value"=>$value,"text"=>$text];
            }
        }
        return $items;
	}

}

