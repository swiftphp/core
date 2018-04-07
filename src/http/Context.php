<?php
namespace swiftphp\core\http;

use swiftphp\core\config\IConfigurable;
use swiftphp\core\config\IConfiguration;

/**
 * 请求,响应上下文
 * @author Tomix
 *
 */
class Context implements IConfigurable
{
    /**
     * 当前请求
     * @var Request
     */
    private $m_request;

    /**
     * 当前响应
     * @var Response
     */
    private $m_response;

    /**
     * 配置实例
     * @var IConfiguration
     */
    private $m_config;

    /**
     * 构造函数
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request=null,Response $response=null)
    {
        $this->m_request=$request;
        $this->m_response=$response;
    }


    /**
     * 注入配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }

    /**
     * 请求
     * @return Request
     */
    public function getRequest()
    {
        return $this->m_request;
    }

    /**
     * 请求
     * @param Request $value
     */
    public function setRequest(Request $value)
    {
        $this->m_request=$value;
    }

    /**
     * 响应
     * @return Response
     */
    public function getResponse()
    {
        return $this->m_response;
    }

    /**
     * 响应
     * @param Response $value
     */
    public function setResponse(Response $value)
    {
        $this->m_response=$value;
    }

}

