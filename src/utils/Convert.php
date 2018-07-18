<?php
namespace swiftphp\core\utils;

/**
 * 数据转换常用方法
 * @author Tomix
 *
 */
class Convert
{
    /**
     * 转换到对象属性值
     * @param unknown $obj
     * @param unknown $propertyName
     * @param unknown $value
     */
    public static function toPropertyValue($obj,$propertyName,$value)
    {
        if(property_exists($obj, $propertyName)){
            $obj->$propertyName=$value;
        }
    }

    /**
     * 使用setter设置属性值
     * @param object $object
     * @param string $propertyName
     * @param mixed $value
     */
    public static function setPropertyValue($object,$propertyName,$value)
    {
        $setter = "set" . ucfirst($propertyName);
        if (method_exists($object, $setter)) {
            $object->$setter($value);
        }
    }

    /**
     * 使用getter获取属性值
     * @param object $object
     * @param string $propertyName
     * @return mixed
     */
    public static function getPropertyValue($object,$propertyName)
    {
        $getter = "get" . ucfirst($propertyName);
        if (method_exists($object, $getter)) {
            return $object->$getter();
        }
        return null;
    }

    /**
     * 使用setter设置属性值
     * @param object $object
     * @param array $keyValueArray
     */
    public static function setPropertyValues($object,$keyValueArray=[])
    {
        foreach ($keyValueArray as $name => $value){
            Convert::setPropertyValue($object, $name, $value);
        }
    }

    /**
     * 获取对象或数组字段值
     * @param object|array $item       数组或对象
     * @param string $field            字段名或数组键
     * @param bool $objectFieldAccess  获取对象属性时,是否允许直接访问对象的属性(默认为false,只能通过getter取值)
     * @return mixed
     */
    public static function getFieldValue($item,$field,$objectFieldAccess=false)
    {
        if(is_array($item) && array_key_exists($field, $item)){
            return $item[$field];
        }else if(is_object($item)){
            return ObjectUtil::getPropertyValue($item, $field,$objectFieldAccess);
        }
        return null;
    }

    /**
     * 设置对象或数组字段值
     * @param object|array $item            数组或对象
     * @param unknown $field                字段名或数组键
     * @param unknown $value                字段值或数组值
     * @param string $objectFieldAccess     设置对象属性时,是否允许直接访问对象的属性(默认为false,只能通过etter设置)
     */
    public static function setFieldValue(&$item,$field,$value,$objectFieldAccess=false)
    {
        if(is_array($item)){
            $item[$field]=$value;
        }else if(is_object($item)){
            ObjectUtil::setPropertyValue($item, $field, $value,$objectFieldAccess);
        }
    }

    /**
     * 将位结构的数组转化为整型值
     * @param $value 位结构的数组(1,2,4...)
     * @return int
     */
    public static function arrayToInt($value=[])
    {
        if(!is_array($value) || count($value) == 0){
            return 0;
        }
        $nReturn = 0;
        foreach ($value as $item){
            if(($nReturn & $item) <= 0){
                $nReturn += $item;
            }
        }
        return $nReturn;
    }

    /**
     * 将位结构的整数转化为位数组
     * @param $value  位结构的整数
     * @return array
     */
    public static function intToArray($value)
    {
        if ($value <= 0){
            return [];
        }else{
            $arr=[];
            $nLeave = $value;
            $nT = 0x01;
            while ($nLeave > 0){
                if (($nLeave & 1) == 1){
                    $arr[]=$nT;
                }
                $nLeave = ($nLeave >> 1);
                if ($nLeave > 0){
                    $nT = ($nT << 1);
                }
            }
            return $arr;
        }
    }

    /**
     * 向位结构的整数添加值
     * @param integer $srcValue
     * @param integer $intValue
     * @return integer
     */
    public static function addBitInt(&$srcValue,$intValue)
    {
        if(empty($srcValue)){
            $srcValue = $intValue;
        }
        else if(($srcValue&$intValue)<=0){
            $srcValue+=$intValue;
        }
        return $srcValue;
    }

    /**
     * 从位结构的整数移除值
     * @param integer $srcValue
     * @param integer $intValue
     * @return integer
     */
    public static function removeBitInt(&$srcValue,$intValue)
    {
        if(($srcValue&$intValue)>0){
            $srcValue-=$intValue;
        }
        return $srcValue;
    }

    /**
     * 检查位结构的整数是否存在某个值
     * @param integer $value
     * @param integer $checkValue
     * @return boolean
     */
    public static function checkBitInt($value,$checkValue)
    {
        return (($value&$checkValue)>0);
    }

    /**
     * 数据库sql语句过滤函数
     * @param $input 输入字符串
     * @return string
     */
    public static function toDbString($input)
    {
        if(is_array($input)){
            foreach ($input as $key=>$value){
                $input[$key]=self::toDbString($value);
            }
        }else if(is_object($input)){
            $array=get_object_vars($input);
            foreach ($array as $key=>$value){
                $input->$key=self::toDbString($value);
            }
        }else{
            //$input=str_replace("'","&#039;",$input);	//替換單引號
            $input=str_replace("'","''",$input);
        }

        return $input;
    }

    /**
     * 转换特殊字符为html实体
     '&' (ampersand) becomes '&amp;'
     '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set.
     ''' (single quote) becomes '&#039;' only when ENT_QUOTES is set.
     '<' (less than) becomes '&lt;'
     '>' (greater than) becomes '&gt;'
     */
    public static function toHtmlEntity($input)
    {
        if(is_array($input)){
            foreach ($input as $key=>$value){
                $input[$key]=self::toHtmlEntity($value);
            }
        }else if(is_object($input)){
            $array=get_object_vars($input);
            foreach ($array as $key=>$value){
                $input->$key=self::toHtmlEntity($value);
            }
        }else{
            $input=htmlspecialchars($input);
        }

        return $input;
    }

    /**
     * 转换数组到对象属性
     * 根据数组下标与对象属性名映射转换
     */
    public static function arrayToObject($array,$obj,$maps=[])
    {
        if(is_array($array) && is_object($obj)){
            foreach($array as $key=>$value){
                $propertyName=$key;
                if(array_key_exists($key, $maps)){
                    $propertyName=$maps[$key];
                }
                if(property_exists($obj,$propertyName)){
                    $obj->$propertyName=$value;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 复制对象属性值
     * @param object $srcObject 	源对象
     * @param object $destObject	引用传递:目标对象
     * @return boolean
     * @deprecated
     */
    public static function copyPropertyValues($srcObject,$destObject,$fieldMap=[])
    {
        if(is_object($srcObject) && is_object($destObject)){
            foreach (get_object_vars($srcObject) as $prop=>$value){
                if(!empty($fieldMap) && is_array($fieldMap) && array_key_exists($prop, $fieldMap)){
                    self::toPropertyValue($destObject, $fieldMap[$prop], $value);
                }else{
                    self::toPropertyValue($destObject, $prop, $value);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 复制数组
     * @param array $srcArray
     * @param array $destArray
     * @param array $fieldMap
     * @return boolean
     */
    public static function copyArray($srcArray,&$destArray,$fieldMap=[])
    {
        if(empty($fieldMap)){
            $destArray=$srcArray;
            return true;
        }else{
            $dim=count($fieldMap)==count($fieldMap,1);
            foreach ($fieldMap as $srcKey=>$destKey){
                if($dim){
                    $srcKey=$destKey;
                }
                $destArray[$destKey]=$srcArray[$srcKey];
            }
            return true;
        }
    }

    /**
     * 深度复制数组到对象属性
     * @param array $array      数组
     * @param object $obj       根对象
     * @param array $classMap   对象类名映射;键对应的数组索引序列化为对象,多层用.号分隔;值第一个元素为类型名,第二个元素指定是否为对象数组
     * @param bool $fieldAccess 是否通过公开字段赋值
     */
    public static function arrayToObjectDeeply($array,$obj,$classMap=[],$fieldAccess=true)
    {
        return self::_arrayToObjectDeeply($array, $obj,$classMap,$fieldAccess,"");
    }

    /**
     * 深度复制数组到对象属性
     * @param array $array      数组
     * @param object $obj       根对象
     * @param array $classMap   对象类名映射;键对应的数组索引序列化为对象,多层用.号分隔;值第一个元素为类型名,第二个元素指定是否为对象数组
     * @param bool $fieldAccess 是否通过公开字段赋值
     * @param string $currentPrefix 当前索引的前缀
     */
    private static function _arrayToObjectDeeply($array,$obj,$classMap=[],$fieldAccess=true,$currentPrefix="")
    {
        $prefix=$currentPrefix;
        if(!empty($prefix)){
            $prefix.=".";
        }
        if(is_array($array) && is_object($obj)){
            $props=[];
            if($fieldAccess){
                $props=array_keys(get_object_vars($obj));
            }
            foreach ($array as $name => $value){
                $key=$prefix.$name;
                if(array_key_exists($key, $classMap)){
                    if(is_array($value)){
                        $map=$classMap[$key];
                        $class=$map[0];
                        $isArray=$map[1];

                        if($isArray && is_array($value)){
                            $objArray=[];
                            foreach ($value as $_name => $_value){
                                $_obj=new $class();
                                self::_arrayToObjectDeeply($_value, $_obj,$classMap,$fieldAccess,$key);
                                $objArray[$_name]=$_obj;
                            }
                            self::_setProperty($obj, $name, $objArray,$props);
                        }else{
                            $_obj=new $class();
                            self::_arrayToObjectDeeply($value, $_obj,$classMap,$fieldAccess,$key);
                            self::_setProperty($obj, $name, $_obj,$props);
                        }
                    }
                }else{
                    self::_setProperty($obj, $name, $value,$props);
                }
            }
        }
    }

    /**
     * 设置对象属性值
     * @param object $obj
     * @param string $name
     * @param mixed $value
     * @param array $publicFields 允许通过公开的字段赋值时,提供对象的公开字段名
     */
    private static function _setProperty($obj,$name,$value,$publicFields=[])
    {
        $setter="set".ucfirst($name);
        if(method_exists($obj, $setter)){
            $obj->$setter($value);
        }else if(in_array($name, $publicFields)){
            $obj->$name=$value;
        }
    }
}

