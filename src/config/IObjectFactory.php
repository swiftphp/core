<?php
namespace swiftphp\core\config;

/**
 * 对象工厂接口
 * @author Tomix
 *
 */
interface IObjectFactory
{
    /**
     * 根据对象ID创建实例
     * @param string $objectId  对象ID
     */
    function create($objectId);

    /**
     * 根据类型名创建实例
     * @param string $class     类型名称
     * @param bool $singleton   是否单例模式,默认为单例模式
     */
    function createByClass($class,$singleton=true);
}
