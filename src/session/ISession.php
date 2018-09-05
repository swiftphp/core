<?php
namespace swiftphp\core\session;

/**
 * session接口
 * @author Tomix
 *
 */
interface ISession
{
    /**
     * 从session读取
     * @param string $key
     */
    function get($key);

    /**
     * 从session读取所有的键值对数据
     */
    function getAll();

    /**
     * 写入session
     * @param string $key
     * @param mixed $value
     */
    function set($key,$value);

    /**
     * 移除session
     * @param string $key
     */
    function remove($key);

    /**
     * 清空所有的session
     */
    function clear();

    /**
     * 获取session id
     */
    function getSessionId();

    /**
     * 动态设置session id,用于外部注入
     * @param string $sessionId
     */
    function setSessionId($sessionId);
}

