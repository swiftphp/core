<?php
namespace swiftphp\core\http;

/**
 * 过滤链
 * @author Tomix
 *
 */
class FilterChain
{
    /**
     *过滤器集合
     * @var array
     */
    private $m_filters=[];

    /**
     * 当前索引
     * @var integer
     */
    private $index=-1;

    /**
     * 构造函数
     * @param array $filters
     */
    public function __construct($filters=[])
    {
        $this->m_filters=$filters;
    }

    /**
     * 添加过滤器
     * @param IFilter $filter
     */
    public function addFilter(IFilter $filter)
    {
        $this->m_filters[]=$filter;
    }

    /**
     * 执行过滤
     * @param Context $context
     */
    public function filter(Context $context)
    {
        $this->index++;
        if(empty($this->m_filters) || $this->index >= count($this->m_filters))
            return;
        $filter=$this->m_filters[$this->index];
        return $filter->filter($context,$this);
    }
}

