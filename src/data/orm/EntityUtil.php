<?php
namespace swiftphp\core\data\orm;

use swiftphp\core\utils\ObjectUtil;
use swiftphp\core\utils\StringUtil;

/**
 * 实体功能类
 * @author Tomix
 *
 */
class EntityUtil
{
    /**
     * 获取实体属性值
     * @param object $model        实体对象
     * @param string $field        实体属性名
     * @param string $fieldAccess  如果属性getter不存在,是否允许直接访问属性,默认为true
     */
    public static function getFieldValue($model,$field,$fieldAccess=true)
    {
        if(ObjectUtil::hasGetter($model, $field)){
            return ObjectUtil::getPropertyValue($model, $field);
        }else if($fieldAccess && property_exists($model, $field)){
            return $model->$field;
        }
    }

    /**
     * 设置实体取属性值
     * @param object $model        实体对象
     * @param string $field        实体属性名
     * @param mixed $value         属性值
     * @param string $fieldAccess  如果属性setter不存在,是否允许直接访问属性,默认为true
     */
    public static function setFieldValue($model,$field,$value,$fieldAccess=true)
    {
        if(ObjectUtil::hasSetter($model, $field)){
            ObjectUtil::setPropertyValue($model, $field, $value);
        }else if($fieldAccess && property_exists($model, $field)){
            $model->$field=$value;
        }
    }

    /**
     * 实体字段是否存在
     * @param object|string $class
     * @param string $field
     * @param string $fieldAccess  如果属性setter/getter不存在,是否允许直接访问属性,默认为true
     * @return boolean
     */
    public static function fieldExists($class,$field,$fieldAccess=true)
    {
        if(ObjectUtil::hasGetterAndSetter($class, $field)){
            return true;
        }else if($field && property_exists($class, $field)){
            return true;
        }
        return false;
    }

    /**
     * 获取数据字段值
     * @param object|array $modelOrArray    数据模型,对象或数组
     * @param string $field                 字段名
     * @param string $fieldAccess           从对象取值时,如果属性getter不存在,是否允许直接访问属性,默认为true
     * @return mixed|unknown
     */
    public static function getDataFieldValue($modelOrArray,$field,$fieldAccess=true)
    {
        if(is_object($modelOrArray)){
            return self::getFieldValue($modelOrArray, $field,$fieldAccess);
        }else if(is_array($modelOrArray) && array_key_exists($field, $modelOrArray)){
            return $modelOrArray[$field];
        }
    }

    /**
     * 数据字段是否存在
     * @param object|array $modelOrArray    数据模型,对象或数组
     * @param string $field                 字段名
     * @param string $fieldAccess           从对象取值时,如果属性getter不存在,是否允许直接访问属性,默认为true
     */
    public static function dataFieldExists($modelOrArray,$field,$fieldAccess=true)
    {
        if(is_object($modelOrArray)){
            return self::fieldExists($modelOrArray, $field,$fieldAccess);
        }else if(is_array($modelOrArray)){
            return array_key_exists($field, $modelOrArray);
        }
        return false;
    }

    /**
     * 设置数据字段值
     * @param object|array $modelOrArray    数据模型,对象或数组
     * @param string $field                 字段名
     * @param mixed $value
     * @param string $fieldAccess           从对象设置值时,如果属性setter不存在,是否允许直接访问属性,默认为true
     */
    public static function setDataFieldValue(&$modelOrArray,$field,$value,$fieldAccess=true)
    {
        if(is_object($modelOrArray)){
            self::setFieldValue($modelOrArray, $field, $value,$fieldAccess);
        }else if(is_array($modelOrArray)){
            $modelOrArray[$field]=$value;
        }
    }


    /**
     * 映射数据库字段名与实体属性名
     * @param object $model
     * @param string $dbField
     * @param string $humpFieldMode
     * @param string $fieldAccess      如果属性getter/setter不存在,是否允许直接访问属性,默认为true
     * @return string|bool
     */
    public static function mapModelField($model,$dbField,$humpFieldMode=true,$fieldAccess=true)
    {
        //与表字段名一致
        if(ObjectUtil::hasGetterAndSetter($model, $dbField) || ($fieldAccess && property_exists($model, $dbField))){
            return $dbField;
        }

        //驼峰命名法
        if($humpFieldMode){
            //转为小写起的驼峰名
            $fd=StringUtil::toHumpString($dbField);
            if(ObjectUtil::hasGetterAndSetter($model, $fd) || ($fieldAccess && property_exists($model, $fd))){
                return $fd;
            }

            //转为大写起的驼峰名
            $fd=ucfirst($fd);
            if(ObjectUtil::hasGetterAndSetter($model, $fd) || ($fieldAccess && property_exists($model, $fd))){
                return $fd;
            }
        }
        return false;
    }

    /**
     * 映射数据库字段名到数组键名
     * @param array $array
     * @param string $dbField
     * @param string $humpFieldMode
     * @return string|bool
     */
    public static function mapArrayKey(array $array,$dbField,$humpFieldMode=true)
    {
        //与表字段名一致
        if(array_key_exists($dbField, $array)){
            return $dbField;
        }
        if($humpFieldMode){
            //转为小写起的驼峰名
            $fd=StringUtil::toHumpString($dbField);
            if(array_key_exists($fd, $array)){
                return $fd;
            }

            //转为大写起的驼峰名
            $fd=ucfirst($fd);
            if(array_key_exists($fd, $array)){
                return $fd;
            }
        }
        return false;

    }

    /**
     * 映射数据库字段名到实体属性名或数组键名
     * @param object|array $modelOrArray
     * @param string $dbField
     * @param string $humpFieldMode
     * @param string $fieldAccess            映射对象属性名时,如果属性getter/setter不存在,是否允许直接访问属性,默认为true
     * @return string|bool
     */
    public static function mapDataField($modelOrArray,$dbField,$humpFieldMode=true,$fieldAccess=true)
    {
        if(is_object($modelOrArray)){
            return self::mapModelField($modelOrArray, $dbField,$humpFieldMode,$fieldAccess);
        }else if(is_array($modelOrArray)){
            return self::mapArrayKey($modelOrArray, $dbField,$humpFieldMode);
        }
    }
}

