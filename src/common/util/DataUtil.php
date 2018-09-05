<?php
namespace swiftphp\core\common\util;

/**
 * 数据通用功能类
 * @author Tomix
 *
 */
class DataUtil
{
    /**
     * 取得树型数据的上级数据(包括自身)
     * @param $source 		数据源
     * @param $idField  	主键字段名
     * @param $pidField		上级数据主键字段名
     * @param $fieldValue	主键字段值
     * @param $includeSelf	是否包括自身
     * @return array
     */
    public static function getAncestors($source,$idField,$pidField,$fieldValue,$includeSelf=true)
    {
        $returnValue=[];
        $current=null;
        foreach($source as $row){
            $idValue=Convert::getFieldValue($row, $idField,true);
            if($idValue==$fieldValue){
                $current=$row;
                if($includeSelf){
                    $returnValue[]=$current;
                }
                break;
            }
        }
        $pidValue=Convert::getFieldValue($current, $pidField,true);
        while($pidValue){
            $current=self::_getAncestors($source,$idField,$pidValue);
            $returnValue[]=$current;
            $pidValue=Convert::getFieldValue($current, $pidField,true);
        }

        //echo $returnValue[1][$idField];
        return array_reverse($returnValue);
    }

    /**
     *  取得树型数据的子级数据(包括自身)
     * @param $source 		数据源
     * @param $idField  	主键字段名
     * @param $pidField		上级数据主键字段名
     * @param $fieldValue	主键字段值
     * @param $level  		树型数据深度,负数表示无限制.默认为无限制
     * @return array
     */
    public static function getOffSprings($source,$idField,$pidField,$fieldValue,$includeSelf=true,$level=-1)
    {
        if(empty($source)){
            return [];
        }

        //元素为对象时,通过第一个元素检测getter
        $idGetter=null;
        $pidGetter=null;
        $first=$source[array_keys($source)[0]];
        $isObject=is_object($first);
        if($isObject){
            $idGetter=ObjectUtil::getGetter($first, $idField);
            $pidGetter=ObjectUtil::getGetter($first, $pidField);
        }

        $returnValue=self::_getOffSprings($source,$isObject,$idField,$idGetter,$pidField,$pidGetter,$fieldValue,$level,$includeSelf);

        return $returnValue;
    }

    /**
     * 树状下拉框数据
     * @param unknown $source
     * @param unknown $idField
     * @param unknown $pidField
     * @param unknown $titleField
     * @param string $separator
     * @param string $prefix
     * @param unknown $level
     * @return unknown[]
     */
    public static function getSelectTree($source,$idField,$pidField,$titleField,$separator="&nbsp;&nbsp;&nbsp;&nbsp;",$prefix="|-",$level=-1)
    {
        $target=[];
        foreach ($source as $item){
            $pid=$item[$pidField];
            if(empty($pid)){
                $id=$item[$idField];
                $title=$item[$titleField];
                $target[$id]=$title;
                self::_getSelectTreeChildren($target,$id,$source,$idField,$pidField,$titleField,$separator,$prefix,$level,1);
            }
        }
        return $target;
    }

    /**
     * 计算数组差集,专用于用数字索引的二维数组比较
     * @param $source
     * @param $subSource
     * @param $idField
     * @return array
     */
    public static function arrayDiff($source,$subSource,$idField)
    {
        $keys=array();
        foreach($subSource as $item){
            $keys[]=Convert::getFieldValue($item, $idField,true);
        }

        $returnValue=[];
        foreach($source as $item){
            $key=Convert::getFieldValue($item, $idField,true);
            if(!in_array($key,$keys)){
                $returnValue[]=$item;
            }
        }
        return $returnValue;
    }

    /**
     * 私有方法:取得树型数据的下级数据,该方法是getOffSprings()的辅助方法
     * @param array $source 		数据源
     * @param boolean $isObject     数据源元素是否为对象
     * @param string $idField  	        主键字段名
     * @param string $idGetter  	数据源元素时,主键字段Getter
     * @param string $pidField		上级数据主键字段名
     * @param string $pidGetter     数据源元素时,上级数据主键字段Getter
     * @param mixed $fieldValue	        搜索字段值
     * @param integer $level        搜索最大深度
     * @param boolean $includeSelf  是否包含自身
     * @param integer $currentLevel 当前搜索深度
     * @return array
     */
    private static function _getOffSprings(&$source,$isObject,$idField,$idGetter,$pidField,$pidGetter,$fieldValue,$level,$includeSelf=true,$currentLevel=0)
    {
        if($level<0){
            $level=9999;
        }
        if($currentLevel>=$level){
            return [];
        }
        $returnValue=[];
        $hasSelf=false;
        foreach($source as $item){
            //当前深度为0时,添加自身
            if($currentLevel == 0 && $includeSelf && !$hasSelf){
                //$value=Convert::getFieldValue($item, $idField,true);
                $value=self::getFieldValue($item, $idField,$isObject,$idGetter);
                if($value==$fieldValue){
                    $returnValue[]=$item;
                    $index=array_search($item,$source);
                    if(null!=$index){
                        unset($source[$index]);
                    }
                    $hasSelf=true;
                    continue;
                }
            }

            //取下级数据
            //$pvalue=Convert::getFieldValue($item, $pidField,true);
            $pvalue=self::getFieldValue($item, $pidField,$isObject,$pidGetter);
            if($pvalue == $fieldValue){
                $returnValue[]=$item;
                $index=array_search($item,$source);
                if(null!=$index){
                    unset($source[$index]);
                }

                //递归取再下级数据
                //$value=Convert::getFieldValue($item, $idField,true);
                $value=self::getFieldValue($item, $idField,$isObject,$idGetter);
                $children=self::_getOffSprings($source,$isObject,$idField,$idGetter,$pidField,$pidGetter,$value,$level,false,$currentLevel+1);
                $returnValue=array_merge($returnValue,$children);
            }
        }
        return $returnValue;
    }

    /**
     * 取字段值
     * @param array|object $item
     * @param string $field
     * @param boolean $isObject
     * @param string $fieldGetter
     * @return mixed
     */
    private static function getFieldValue($item,$field,$isObject=false, $fieldGetter=null)
    {
        if(!$isObject){
            return $item[$field];
        }else if($isObject && $fieldGetter){
            return $item->$fieldGetter();
        }else if($isObject && property_exists($item, $field)){
            return $item->$field;
        }
        return null;
    }

    /**
     * 私有方法:取得树型数据的上级数据.该方法是getAncestors()的辅助方法
     * @param $source 		数据源
     * @param $idField  	主键字段名
     * @param $fieldValue	主键字段值
     * @return array
     */
    private static function _getAncestors($source,$idField,$fieldValue)
    {
        foreach($source as $row){
            $value=Convert::getFieldValue($row, $idField,true);
            if($value == $fieldValue){
                return $row;
            }
        }
    }

    /**
     * 树状下拉框数据
     * @param unknown $target
     * @param unknown $pId
     * @param unknown $source
     * @param unknown $idField
     * @param unknown $pidField
     * @param unknown $titleField
     * @param unknown $separator
     * @param unknown $prefix
     * @param unknown $level
     * @param unknown $currentLevel
     */
    private static function _getSelectTreeChildren(&$target,$pId,$source,$idField,$pidField,$titleField,$separator,$prefix,$level,$currentLevel)
    {
        if($level<0){
            $level=9999;
        }
        if($currentLevel>$level){
            return;
        }
        $sep="";
        for($i=0;$i<$currentLevel;$i++){
            $sep.=$separator;
        }
        foreach ($source as $item){
            $_pid=$item[$pidField];
            if($_pid==$pId){
                $id=$item[$idField];
                $title=$sep.$prefix.$item[$titleField];
                $target[$id]=$title;
                self::_getSelectTreeChildren($target,$id,$source,$idField,$pidField,$titleField,$separator,$prefix,$level,$currentLevel+1);

            }
        }
    }
}

