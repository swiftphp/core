<?php
namespace swiftphp\core\data\orm\mapping;

/**
 * 一对多集合关联模型
 * @author Tomix
 *
 */
class OneToManyJoin extends QueryJoinCollection
{
    /**
     * 关联字段的类型名(需要同步load时必须)
     * @var string
     */
    protected $class;

    /**
     * 是否级联操作
     * @var string
     */
    protected $sync=true;

    /**
     * 排序表达式
     * @var string
     */
    protected $order;

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
}

