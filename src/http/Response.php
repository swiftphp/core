<?php
namespace swiftphp\core\http;

/**
 * 输出响应类型
 * @author Tomix
 *
 */
class Response
{

    /**
     * 当前的content-type
     * @var string
     */
    protected $m_contentType="text/html";

    /**
     * 字符集
     * @var string
     */
    protected $m_charset = "utf-8";

    /**
     * 状态码
     * @var integer
     */
    protected $m_code = 200;

    /**
     * 输出头部
     * @var array
     */
    protected $m_headers = [];

    /**
     * 输出代理
     * @var IOutput
     */
    protected $m_output=null;

    /**
     * 输出是否已关闭
     * @var string
     */
    protected $m_closed=false;

    /**
     * 字符集
     */
    public function getCharset()
    {
        return $this->m_charset;
    }

    /**
     * 字符集
     * @param string $value
     */
    public function setCharset($value)
    {
        $this->m_charset=$value;
    }

    /**
     * 获取content-type
     * @return string
     */
    public function getContentType()
    {
        return $this->m_contentType;
    }

    /**
     * 设置content-type
     * @param string $value
     */
    public function setContentType($value)
    {
        $this->m_contentType=$value;
    }

    /**
     * 状态码
     */
    public function getCode()
    {
        return $this->m_code;
    }

    /**
     * 状态码
     * @param integer $value
     */
    public function setCode($value)
    {
        $this->m_code=$value;
    }

    /**
     * 输出头部
     */
    public function getHeaders()
    {
        return $this->m_headers;
    }

    /**
     * 输出头部
     * @param array $value
     */
    public function setHeaders($value)
    {
        $this->m_headers=$value;
    }

    /**
     * 获取当前输出代理
     * @return \swiftphp\core\http\IOutput
     */
    public function getOutput()
    {
        return $this->m_output;
    }

    /**
     * 设置当前的输出代理
     * @param IOutput $value
     */
    public function setOutput(IOutput $value)
    {
        $this->m_output=$value;
    }

    /**
     * 添加输出头部
     * @param string $name
     * @param string $value
     */
    public function addHeader($name,$value="")
    {
        $this->m_headers[$name]=$value;
    }

    /**
     * 移除输出头部
     * @param string $name
     */
    public function removeHeader($name)
    {
        if(array_key_exists($name, $this->m_headers))
            unset($this->m_headers[$name]);
    }

    /**
     * 关闭输出
     * @param bool $value
     */
    public function close($value=1)
    {
        $this->m_closed=$value;
    }

    /**
     * 输出是否已关闭
     * @return string
     */
    public function closed()
    {
        return $this->m_closed;
    }
}