<?php
namespace swiftphp\core\data\types;

/**
 * 常用数据类型(部分)
 * @author Tomix
 *
 */
abstract class Type
{
    /**
     * 字符串
     * @var string
     */
    const STRING="string";

    /**
     * 整数
     * @var string
     */
    const INTEGER="integer";

    /**
     * 浮点数
     * @var string
     */
    const DOUBLE="double";

    /**
     * 日期时间
     * @var string
     */
    const DATETIME="datetime";

    /**
     * 日期
     * @var string
     */
    const DATE="date";

    /**
     * 布尔
     * @var string
     */
    const BOOLEAN="boolean";

    /**
     * 类型名称
     * @var string
     */
    protected $m_typeName=Type::STRING;

    /**
     * 类型映射
     * @var array
     */
    protected static $m_typeMap=[
        Type::STRING => StringType::class,
        Type::INTEGER => IntegerType::class,
        Type::DOUBLE => DoubleType::class,
        Type::DATETIME => DateTimeType::class,
        Type::DATE => DateType::class,
        Type::BOOLEAN => BooleanType::class,
    ];

    /**
     * 类型对象映射
     * @var array
     */
    protected static $m_typeObjMap=[];

    /**
     * 获取类型名
     * @return string
     */
    public function getName()
    {
        return $this->m_typeName;
    }

    /**
     * 根据类型名获取类型对象
     * @param string $typeName
     * @return Type
     */
    public static function getType($typeName)
    {
        if(array_key_exists($typeName, self::$m_typeObjMap)){
            return self::$m_typeObjMap[$typeName];
        }
        if(array_key_exists($typeName, self::$m_typeMap)){
            $class=self::$m_typeMap[$typeName];
            self::$m_typeObjMap[$typeName]=new $class();
            return self::$m_typeObjMap[$typeName];
        }
        return null;
    }
}

