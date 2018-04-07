<?php
namespace swiftphp\core\http;

/**
 * http请求实体
 * @author Tomix
 *
 */
class Request
{
    /**
     * 请求方法
     * @var string
     */
    public $method="GET";

    /**
     * 字符编码
     * @var string
     */
    public $charset="utf-8";

    /**
     * 内容类型
     * @var string
     */
    public $contentType = "text/html";

    /**
     * 内容长度
     * @var string
     */
    public $contentLength=0;

    /**
     * 协议
     * @var string
     */
    public $protocol="HTTP/1.1";

    /**
     * 协议头
     * @var string
     */
    public $scheme="http";

    /**
     * 主机名
     * @var string
     */
    public $host="";

    /**
     * 端口
     * @var string
     */
    public $port=80;

    /**
     * 请求头部
     * @var string
     */
    public $headers=[];

    /**
     * 请求URI
     * @var string
     */
    public $uri;

    /**
     * GET参数
     * @var string
     */
    public $get=[];

    /**
     * POST参数
     * @var string
     */
    public $post=[];

    /**
     * 获取请求参数(搜索顺序:get,post)
     * @param string $name
     */
    public function getRequestParameter($name,$default="")
    {
        if(array_key_exists($name, $this->get))
            return $this->get[$name];
        else if(array_key_exists($name, $this->post))
            return $this->post[$name];
        return $default;
    }
}