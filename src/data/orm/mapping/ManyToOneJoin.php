<?php
namespace swiftphp\core\data\orm\mapping;

/**
 * 多对一关联模型
 * 配置:<entity name="category" class="ProdCategory" table="pro_category" alias="c" on="c.id=p.category_id" columns="*" />
 * 其中:pro_category可选,如没有提供,则DAO应从类型映射中搜索表名,columns只在查询时有效
 * @author Tomix
 *
 */
class ManyToOneJoin
{
    /**
     * 关联节点表名
     * @var string
     */
    private $table;

    /**
     * 关联字段的类型名
     * @var string
     */
    private $class;

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
     * 查询列集表达式
     * @var string
     */
    private $columns;

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
     * 获取关联字段的类型名
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * 设置关联字段的类型名
     * @param unknown $value
     */
    public function setClass($value)
    {
        $this->class=$value;
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

    /**
     * 获取查询列集表达式
     * @return string
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * 设置查询列集表达式
     * @param string $value
     */
    public function setColumns($value)
    {
        $this->columns=$value;
    }
}

