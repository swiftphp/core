<?php
namespace swiftphp\core\utils;

/**
 * 对象常用功能类
 * @author Tomix
 *
 */
class ObjectUtil
{
    /**
     * 设置对象属性
     * @param object $obj           对象
     * @param string $propertyName  属性名
     * @param mixed $value          属性值
     */
    public static function setPropertyValue($obj,$propertyName,$value)
    {
        $setter = "set" . ucfirst($propertyName);
        if (method_exists($obj, $setter)) {
            $obj->$setter($value);
        }
    }

    /**
     * 获取对象属性值
     * @param object $obj           对象
     * @param string $propertyName
     * @return mixed
     */
    public static function getPropertyValue($obj,$propertyName)
    {
        $getter = "get" . ucfirst($propertyName);
        if (method_exists($obj, $getter)) {
            return $obj->$getter();
        }
    }
}

