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

