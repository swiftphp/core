<?php
namespace swiftphp\core\web;

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
}

