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
     * @return void
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
     * @param string $propertyName  属性名
     * @return mixed
     */
    public static function getPropertyValue($obj,$propertyName)
    {
        $getter = "get" . ucfirst($propertyName);
        if (method_exists($obj, $getter)) {
            return $obj->$getter();
        }
    }

    /**
     * 根据属性名获取getter方法名
     * @param object|string $class  对象或类型名
     * @param string $propertyName  属性名
     * @return string
     */
    public static function getGetter($class,$propertyName)
    {
        $getter = "get" . ucfirst($propertyName);
        if (method_exists($class, $getter)) {
            return $getter;
        }
        return "";
    }

    /**
     * 根据属性名获取setter方法名
     * @param object|string $class  对象或类型名
     * @param string $propertyName  属性名
     * @return string
     */
    public static function getSetter($class,$propertyName)
    {
        $setter = "set" . ucfirst($propertyName);
        if (method_exists($class, $setter)) {
            return $setter;
        }
        return "";
    }

    /**
     * 是否有属性getter
     * @param object|string $class  对象或类型名
     * @param string $propertyName  属性名
     */
    public static function hasGetter($class,$propertyName)
    {
        return !empty(self::getGetter($class, $propertyName));
    }

    /**
     * 是否有属性setter
     * @param object|string $class  对象或类型名
     * @param string $propertyName  属性名
     */
    public static function hasSetter($class,$propertyName)
    {
        return !empty(self::getSetter($class, $propertyName));
    }

    /**
     * 是否同时有属性setter与getter
     * @param object|string $class  对象或类型名
     * @param string $propertyName  属性名
     */
    public static function hasGetterAndSetter($class,$propertyName)
    {
        return self::hasGetter($class, $propertyName)&&self::hasSetter($class, $propertyName);
    }
}

