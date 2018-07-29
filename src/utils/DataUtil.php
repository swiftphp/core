<?php
namespace swiftphp\core\utils;

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
        $returnValue=self::_getOffSprings($source,$idField,$pidField,$fieldValue,$level);

        //添加自身
        if($includeSelf){
            foreach($source as $item){
                $value=Convert::getFieldValue($item, $idField,true);
                if($value == $fieldValue){
                    $returnValue[]=$item;
                    break;
                }
            }
        }

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
     * 私有方法:取得树型数据的子级数据,该方法是getOffSprings()的辅助方法
     * @param $source 		数据源
     * @param $idField  	主键字段名
     * @param $pidField		上级数据主键字段名
     * @param $fieldValue	主键字段值
     * @return array
     */
    private static function _getOffSprings($source,$idField,$pidField,$fieldValue,$level,$currentLevel=0)
    {
        if($level<0){
            $level=9999;
        }
        if($currentLevel>=$level){
            return [];
        }
        $returnValue=[];
        foreach($source as $item){
            $pvalue=Convert::getFieldValue($item, $pidField,true);
            if($pvalue == $fieldValue){
                $returnValue[]=$item;
                $index=array_search($item,$source);
                if(null!=$index){
                    unset($source[$index]);
                }
            }
        }
        $rootArray=$returnValue;
        foreach($rootArray as $item){
            $value=Convert::getFieldValue($item, $idField,true);
            $temp=self::_getOffSprings($source,$idField,$pidField,$value,$level,$currentLevel+1);
            if(count($temp)>0){
                $returnValue=array_merge_recursive($returnValue,$temp);
            }
        }
        return $returnValue;
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

