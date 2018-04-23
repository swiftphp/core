<?php
namespace swiftphp\core\data\orm\mapping;

/**
 * 一对多集合关联模型
 * @author Tomix
 *
 */
class OneToManyJoin
{
    /**
     * 是否级联操作
     * @var string
     */
    private $sync=true;

    /**
     * 查询列集表达式
     * @var string
     */
    private $columns="";

    /**
     * 排序表达式
     * @var string
     */
    private $order;

    /**
     * 关联集
     * @var array
     */
    private $joins=[];

    /**
     * 是否级联操作
     * @return bool
     */
    public function getSync()
    {
        return $this->sync;
    }

    /**
     * 是否级联操作
     * @param bool $value
     */
    public function setSync($value)
    {
        $this->sync=$value;
    }

    /**
     * 排序表达式
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * 排序表达式
     * @param string $value
     */
    public function setOrder($value)
    {
        $this->order=$value;
    }

    /**
     * 查询列集表达式
     * @return string
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * 查询列集表达式
     * @param string $value
     */
    public function setColumns($value)
    {
        $this->columns=$value;
    }

    /**
     * 关联集
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * 关联集
     * @param array $value
     */
    public function setJoins(array $value)
    {
        $this->joins=$value;
    }

    /**
     * 添加关联
     * @param string $joinTableName
     * @param Join $value
     */
    public function addJoin($joinTableName, Join $value)
    {
        $this->joins[$joinTableName]=$value;
    }

    /**
     * 移除关联
     * @param string $joinTableName
     */
    public function removeJoin($joinTableName)
    {
        if(array_key_exists($joinTableName, $this->joins)){
            unset($this->joins[$joinTableName]);
        }
    }

    /**
     * 根据关联表名获取关联模型
     * @param string $joinTableName
     * @return Join
     */
    public function getJoin($joinTableName)
    {
        if(array_key_exists($joinTableName, $this->joins)){
            return $this->joins[$joinTableName];
        }
        return null;
    }
}

