<?php
namespace swiftphp\core\system;


/**
 * 缓存接口
 * @author Tomix
 *
 */
interface ICacher
{
    /**
     * 读取缓存
     * @param string $key
     */
    function get($key);

    /**
     * 写入缓存
     * @param string $key
     * @param mixed $value
     */
    function set($key,$value);

    /**
     * 移除缓存
     * @param string $key
     */
    function remove($key);

    /**
     * 清空所有的缓存
     */
    function clear();

    /**
     * 获取缓存时间
     * @param string $key
     */
    function getCacheTime($key);
}