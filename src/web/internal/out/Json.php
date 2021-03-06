<?php
namespace swiftphp\core\web\internal\out;

/**
 * Json输出代理
 * @author Tomix
 *
 */
class Json extends Base
{
    /**
     * 构造
     * @param mixed $content
     */
    public function __construct($content="")
    {
        parent::__construct($content);
    }

    /**
     * 输出
     * {@inheritDoc}
     * @see \swiftphp\core\http\IOutput::output()
     */
    public function output()
    {
        echo json_encode($this->m_content);
    }
}

