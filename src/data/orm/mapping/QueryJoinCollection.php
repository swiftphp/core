<?php
namespace swiftphp\core\data\orm\mapping;

/**
 * 查询相关的Join关联集合
 * @author Administrator
 *
 */
class QueryJoinCollection extends JoinCollection
{

    /**
     * 查询列集表达式
     * @var string
     */
     protected $columns="";

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
}

