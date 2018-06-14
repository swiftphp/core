<?php
namespace swiftphp\core\system;

/**
 * 打印输出接口
 * @author Tomix
 *
 */
interface IPrinter
{
    /**
     * 打印消息
     * @param string $msg        需要打印的消息
     */
    function print($msg);
}

