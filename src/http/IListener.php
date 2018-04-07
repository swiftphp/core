<?php
namespace swiftphp\core\http;

/**
 * 监听器接口
 * @author Tomix
 *
 */
interface IListener
{
    /**
     * 上下文执行过滤前
     * @param Context $context
     */
    function listenBefore(Context $context);

    /**
     * 上下文执行过滤后
     * @param Context $context
     */
    function listenAfter(Context $context);
}

