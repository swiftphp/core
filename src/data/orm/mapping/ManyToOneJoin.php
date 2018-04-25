<?php
namespace swiftphp\core\data\orm\mapping;

/**
 * 多对一关联模型
 * 配置:<entity name="category" class="ProdCategory" table="pro_category" alias="c" on="c.id=p.category_id" columns="*" />
 * 其中:pro_category可选,如没有提供,则DAO应从类型映射中搜索表名,columns只在查询时有效
 * @author Tomix
 *
 */
class ManyToOneJoin extends QueryJoinCollection
{
    /**
     * 关联字段的类型名(需要同步load时必须)
     * @var string
     */
    protected $class;

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
}

