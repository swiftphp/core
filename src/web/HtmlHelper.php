<?php
namespace swiftphp\core\web;

use swiftphp\core\utils\ObjectUtil;

/**
 * HTML相关帮助类
 * @author Tomix
 *
 */
class HtmlHelper
{
    /**
     * 读取标签属性
     * @param string $outerHtml
     * @return mixed[]
     */
    public static function getTagAttributes($outerHtml="")
    {
        $arr_array=[];
        $outerHtml=substr($outerHtml,0,strpos($outerHtml,">")+1);
        $pattern="/\s[a-zA-Z0-9_]{1,}=\"[^\"]{0,}\"/";
        $matches =[];
        preg_match_all($pattern,$outerHtml,$matches,PREG_SET_ORDER);
        foreach($matches as $match){
            $att=$match[0];
            $pos=strpos($att,"=");
            $key=trim(substr($att,0,$pos));
            $value=substr($att,$pos+1);
            $value=str_replace("\"","",$value);
            $arr_array[$key]=$value;
        }
        return $arr_array;
    }

    /**
     * 获取标签内部内容
     * @param string $outerHtml
     * @param string $tagName
     * @return string
     */
    public static function getTagInnerHtml($outerHtml,$tagName)
    {
        $pattern="/<".$tagName." [^>]*>(.*)<\/".$tagName.">/s";
        $matches=[];
        if(preg_match($pattern, $outerHtml,$matches)){
            return $matches[1];
        }
        return "";
    }

    /**
     * 获取参数值,多维参数用点号(.)分隔
     * @param array|object $inputParam 输入参数
     * @param string $paramKey          参数键
     * @return mixed
     */
    public static function getUIParams($inputParam, $paramKey)
    {
        if(is_object($inputParam)){
            return self::getUIParamsFromObject($inputParam, $paramKey);
        }else if(is_array($inputParam)){
            return self::getUIParamsFromArray($inputParam,$paramKey);
        }
        return false;
    }

    /**
     * 从对象属性取值
     * @param object $inputParam
     * @param string $paramKey
     * @return mixed
     */
    private static function getUIParamsFromObject($inputParam, $paramKey)
    {
        //getter取值
        $getter=ObjectUtil::getGetter($inputParam, $paramKey);
        if($getter!=null){
            return $inputParam->$getter();
        }

        //从属性取值
        $vars=get_object_vars($inputParam);
        if(array_key_exists($paramKey, $vars)){
            return $inputParam->$paramKey;
        }

        //分段取参数:key1.key2.key3...
        if(strpos($paramKey, ".")>0){
            $keys=explode(".", $paramKey);
            $value=self::getUIParamsFromObject($inputParam,$keys[0]);//根据第一段取得对象或数组值
            if($value){
                unset($keys[0]);
                return self::getUIParams($value, implode(".", $keys));
            }
        }

        //匹配不到参数,返回false
        return false;
    }

    /**
     * 从对象属性取值
     * @param array $inputParam
     * @param string $paramKey
     * @return mixed
     */
    private static function getUIParamsFromArray($inputParam, $paramKey)
    {
        if(array_key_exists($paramKey, $inputParam)){
            return $inputParam[$paramKey];
        }

        //分段取参数:key1.key2.key3...
        if(strpos($paramKey, ".")>0){
            $keys=explode(".", $paramKey);
            $value=self::getUIParamsFromArray($inputParam,$keys[0]);//根据第一段取得对象或数组值
            if($value){
                unset($keys[0]);
                return self::getUIParams($value, implode(".", $keys));
            }
        }

        return false;
    }
}

