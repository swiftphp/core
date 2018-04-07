<?php
namespace swiftphp\core\data\orm\mapping;

use swiftphp\core\data\orm\mapping\Join;

/**
 * 删除关联模型
 * @author Tomix
 *
 */
class DeleteJoin
{
    /**
     * 关联集.键为关联表名，值为关联模型实例
     * @var array
     */
    private $joins=[];

    /**
     * 关联集.键为关联表名，值为关联模型实例
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * 关联集.键为关联表名，值为关联模型实例
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