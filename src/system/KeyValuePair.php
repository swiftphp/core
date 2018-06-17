<?php
namespace swiftphp\core\system;

/**
 * 键值对类型
 * @author Tomix
 *
 */
class KeyValuePair
{
    /**
     * 键
     * @var mixed
     */
    private $key;

    /**
     * 值
     * @var mixed
     */
    private $value;

    /**
     * 获取键
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * 设置键
     * @param mixed $value
     */
    public function setKey($value)
    {
        $this->key = $value;
    }

    /**
     * 获取值
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * 设置值
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * 构造函数
     * @param mixed $key
     * @param mixed $value
     */
    public function __construct($key="",$value="")
    {
        $this->key=$key;
        $this->value=$value;
    }
}

