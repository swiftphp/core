<?php
namespace swiftphp\core\data\orm\mapping;

/**
 * 关联模型
 * @author Tomix
 *
 */
class Join
{
    /**
     * 关联节点表名
     * @var string
     */
    private $table;

    /**
     * 关联节点表别名
     * @var string
     */
    private $alias;

    /**
     * 关联条件
     * @var string
     */
    private $on;

    /**
     * 获取关联节点表名
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * 关联节点表名
     * @param string $value
     */
    public function setTable($value)
    {
        $this->table=$value;
    }

    /**
     * 关联节点表别名
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 关联节点表别名
     * @param string $value
     */
    public function setAlias($value)
    {
        $this->alias=$value;
    }

    /**
     * 关联条件
     * @return string
     */
    public function getOn()
    {
        return $this->on;
    }

    /**
     * 关联条件
     * @param string $value
     */
    public function setOn($value)
    {
        $this->on=$value;
    }
}