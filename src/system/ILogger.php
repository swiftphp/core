<?php
namespace swiftphp\core\system;

/**
 * 日志记录接口
 * @author Tomix
 *
 */
interface ILogger
{
    /**
     * 写入日志
     * @param string $message
     * @param string $type
     * @param string $prefix
     */
    function log($message,$type="error",$prefix="err");
}

