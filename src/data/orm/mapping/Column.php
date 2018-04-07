<?php
namespace swiftphp\core\data\orm\mapping;

/**
 * 数据列模型
 *
 * @author Tomix
 *
 */
class Column
{
    /**
     * 字段名
     * @var string
     */
    private $m_name;

    /**
     * 字段在数据库中的类型
     * @var string
     */
    private $m_dbType;

    /**
     * 字段数据类型
     * @var string
     */
    private $m_type;

    /**
     * 是否可为NULL
     * @var string
     */
    private $m_nullable = true;

    /**
     * 是否为键列
     * @var string
     */
    private $m_primary = false;

    /**
     * 是否唯一键列
     * @var string
     */
    private $m_unique = false;

    /**
     * 是否自动递增
     * @var string
     */
    private $m_increment = false;

    /**
     * 默认值
     * @var mixed
     */
    private $m_default;

    /**
     * 字段名
     * @return string
     */
    public function getName()
    {
        return $this->m_name;
    }

    /**
     * 字段名
     * @param string $value
     */
    public function setName($value)
    {
        $this->m_name = $value;
    }

    /**
     * 字段在数据库中的类型
     * @return string
     */
    public function getDbType()
    {
        return $this->m_dbType;
    }

    /**
     * 字段在数据库中的类型
     * @param string $value
     */
    public function setDbType($value)
    {
        $this->m_dbType=$value;
    }

    /**
     * 字段数据类型
     * @return string
     */
    public function getType()
    {
        return $this->m_type;
    }

    /**
     * 字段数据类型
     * @param string $value
     */
    public function setType($value)
    {
        $this->m_type = $value;
    }

    /**
     * 是否可为NULL
     * @return bool
     */
    public function getNullable()
    {
        return $this->m_nullable;
    }

    /**
     * 是否可为NULL
     * @param bool $value
     */
    public function setNullable($value)
    {
        $this->m_nullable = $value;
    }

    /**
     * 是否为键列
     * @return bool
     */
    public function getPrimary()
    {
        return $this->m_primary;
    }

    /**
     * 是否为键列
     * @param bool $value
     */
    public function setPrimary($value)
    {
        $this->m_primary= $value;
    }

    /**
     * 是否唯一键列
     * @return bool
     */
    public function getUnique()
    {
        return $this->m_unique;
    }

    /**
     * 是否唯一键列
     * @param bool $value
     */
    public function setUnique($value)
    {
        $this->m_unique= $value;
    }

    /**
     * 是否自动递增
     * @return bool
     */
    public function getIncrement()
    {
        return $this->m_increment;
    }

    /**
     * 是否自动递增
     * @param bool $value
     */
    public function setIncrement($value)
    {
        $this->m_increment= $value;
    }

    /**
     * 默认值
     * @return mixed
     */
    public function getDefault()
    {
        return $this->m_default;
    }

    /**
     * 默认值
     * @param mixed $value
     */
    public function setDefault($value)
    {
        $this->m_default = $value;
    }
}