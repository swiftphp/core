<?php
namespace swiftphp\core\data\orm\mapping;

/**
 * 查询关联模型
 * @author Tomix
 *
 */
class SelectJoin
{
    /**
     * 查询列
     * @var string
     */
    private $columns="";

    /**
     * 关联集.键为表名，值为模型实例
     * @var array
     */
    private $joins=[];

    /**
     * 查询列
     * @return string
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * 查询列
     * @param string $value
     */
    public function setColumns($value)
    {
        $this->columns=$value;
    }

    /**
     * 关联集.键为表名，值为模型实例
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * 关联集.键为表名，值为模型实例
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
     * @param string $joinTableName 关联表名
     */
    public function removeJoin($joinTableName)
    {
        if(array_key_exists($joinTableName, $this->joins)){
            unset($this->joins[$joinTableName]);
        }
    }

    /**
     * 模拟关联表名获取关联模型实例
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