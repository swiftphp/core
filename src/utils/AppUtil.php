<?php
namespace swiftphp\core\utils;

/**
 * 应用通用功能类
 * @author Tomix
 *
 */
class AppUtil
{
    /**
     * 取得根域名,不带端口号(如:domain.com)
     * @return string
     */
    public static function getDomain()
    {
        $domain=self::getAppDomain();
        $arr=explode(".", $domain);
        $c=count($arr);
        $root="";
        if($c>0)$root=$arr[$c-1];
        if($c>1)$root=$arr[$c-2].".".$root;
        if(strpos($root, ":")){
            $root=substr($root, 0,strpos($root, ":"));
        }
        return $root;
    }

    /**
     * 取得主机名(如:sub.domain.com:8080)
     * @return string
     */
    public static function getAppDomain()
    {
        return $_SERVER["HTTP_HOST"];
    }

    /**
     * 获取客户端IP
     * @return string|unknown
     */
    public static function getClientIp()
    {
        if (getenv("HTTP_CLIENT_IP")){
            $ip = getenv("HTTP_CLIENT_IP");
        }
        else if (getenv("HTTP_X_FORWARDED_FOR")){
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        }
        else if (getenv("HTTP_X_FORWARDED")){
            $ip = getenv("HTTP_X_FORWARDED");
        }
        else if (getenv("HTTP_FORWARDED_FOR")){
            $ip = getenv("HTTP_FORWARDED_FOR");
        }
        else if (getenv("HTTP_FORWARDED")){
            $ip = getenv("HTTP_FORWARDED");
        }
        else{
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }
}

