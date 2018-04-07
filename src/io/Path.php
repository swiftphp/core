<?php
namespace swiftphp\core\io;

/**
 * 路径相关
 * @author Administrator
 *
 */
class Path
{
    /**
     * 获取文件目录名
     * @param string $path
     * @return mixed
     */
    public static function getDirName($path)
    {
        return pathinfo($path,PATHINFO_DIRNAME);
    }

    /**
     * 取得文件名
     * @param string $path
     * @return string
     */
    public static function getFileBaseName($path)
    {
        return pathinfo($path,PATHINFO_BASENAME);
    }

    /**
     * 取得文件名,不包括扩展名
     * @param string $path
     * @return string
     */
    public static function getFileBaseNameWithoutExt($path)
    {
        $baseName = pathinfo($path,PATHINFO_BASENAME);
        $extName= self::getFileExtName($path);
        $pos=strrpos($baseName, $extName);
        if($pos>0){
            $baseName=substr($baseName, 0,$pos-1);
        }
        return $baseName;
    }

    /**
     * 取得文件扩展名
     * @param string $path
     * @return string
     */
    public static function getFileExtName($path)
    {
        return pathinfo($path,PATHINFO_EXTENSION);
    }

    /**
     * 连接路径字符串
     * @param string $path1
     * @param string $path2
     * @return string
     */
    public static function combinePath($path1,$path2)
    {
        return rtrim($path1,"/")."/".ltrim($path2,"/");
    }
}

