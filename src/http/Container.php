<?php
namespace swiftphp\core\http;

use swiftphp\core\config\IConfiguration;
use swiftphp\core\system\ILogger;
use swiftphp\core\BuiltInConst;
use swiftphp\core\config\ObjectFactory;
use swiftphp\core\io\Path;
use swiftphp\core\system\IRunnable;
use swiftphp\core\config\IConfigurable;

/**
 * 主入口容器
 * @author Tomix
 *
 */
class Container implements IRunnable,IConfigurable
{
    /**
     * 当前运行的配置实例
     * @var IConfiguration
     */
    private $m_config = null;

    /**
     * 上下文
     * @var Context
     */
    private $m_context=null;

    /**
     * 所有过滤器
     * @var IFilter[]
     */
    private $m_filters=[];

    /**
     * 所有监听器,原则上没有执行顺序
     * @var IListener[]
     */
    private $m_listeners=[];

    /**
     * 是否调试模式
     * @var string
     */
    private $m_debug=false;

    /**
     * 日志记录器
     * @var ILogger
     */
    private $m_logger=null;

    /**
     * 严重错误输出模板
     * @var string
     */
    private $m_errorTemplate="";

    /**
     * 注入配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }

    /**
     * 是否为调试模式
     * @param bool $value
     */
    public function setDebug($value)
    {
        $this->m_debug=$value;
    }

    /**
     * 设置日志记录器
     * @param ILogger $value
     */
    public function setLogger(ILogger $value)
    {
        $this->m_logger=$value;
    }

    /**
     * 严重错误时输出的消息模板
     * @param string $value
     */
    public function setErrorTemplate($value)
    {
        $this->m_errorTemplate=$value;
    }

    /**
     * 注入过滤器列表
     * @param IFilter[] $value
     */
    public function setFilters(array $value)
    {
        $this->m_filters=$value;
    }

    /**
     * 注入监听器列表
     * @param IListener[] $value
     */
    public function setListeners(array $value)
    {
        $this->m_listeners=$value;
    }

    /**
     *容器执行主入口
     */
    public function run()
    {
        try{

            //初始化上下文
            $this->initContext();

            //开始监听
            $this->listenBefore();

            //执行过滤
            $this->doFilter();

            //响应输出
            $this->response();

            //结束监听
            $this->listenAfter();
        }catch (\Exception $ex){
            if(!$this->m_debug){
                //消息
                $msg=$ex->getCode().":".$ex->getMessage()."\r\n".$ex->getTraceAsString();

                //记录到日志
                if(!empty($this->m_logger)){
                    try{
                        $this->m_logger->log($msg,"exception","ex");
                    }catch (\Exception $e){}
                }

                //如果模板文件存在，则按模板输出
                $tempFile=Path::combinePath($this->m_config->getBaseDir(), $this->m_errorTemplate);
                if(is_file($tempFile)){
                    echo file_get_contents($tempFile);
                }else{
                    echo $msg;
                }

                //退出
                exit;
            }else{
                throw $ex;
            }
        }
    }

    /**
     * 初始化上下文
     * @return Context
     */
    private function initContext()
    {
        //request instance;
        $req=new Request();

        //request headers
        $req->headers=[];
        foreach ($_SERVER as $name => $value){
            if(strpos($name, "HTTP_")===0){
                $key=str_replace(" ", "-", ucwords(str_replace("_", " ", substr(strtolower($name), 5))));
                $req->headers[$key]=$value;
            }
        }
        if (isset($_SERVER["PHP_AUTH_DIGEST"])) {
            $req->headers["Authorization"] = $_SERVER["PHP_AUTH_DIGEST"];
        } elseif (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
            $req->headers["Authorization"] = base64_encode($_SERVER["PHP_AUTH_USER"] . ":" . $_SERVER["PHP_AUTH_PW"]);
        }
        if (isset($_SERVER["CONTENT_LENGTH"])) {
            $req->headers["Content-Length"] = $_SERVER["CONTENT_LENGTH"];
        }
        if (isset($_SERVER["CONTENT_TYPE"])) {
            $req->headers["Content-Type"] = $_SERVER["CONTENT_TYPE"];
        }
        $req->charset=array_key_exists("HTTP_ACCEPT_CHARSET", $req->headers)?$req->headers["HTTP_ACCEPT_CHARSET"]:"utf-8";
        $req->contentType=array_key_exists("CONTENT_TYPE", $req->headers)?$req->headers["CONTENT_TYPE"]:"text/html";
        $req->contentLength=array_key_exists("CONTENT_LENGTH", $req->headers)?$req->headers["CONTENT_LENGTH"]:0;

        $req->method=$_SERVER["REQUEST_METHOD"];
        $req->protocol=$_SERVER["SERVER_PROTOCOL"];
        $req->scheme=$_SERVER["REQUEST_SCHEME"];
        $req->host=$_SERVER["HTTP_HOST"];
        $req->port=$_SERVER["SERVER_PORT"];
        $req->uri=$_SERVER["REQUEST_URI"];
        $req->get=$_GET;
        $req->post=$_POST;

        //response
        $rsp=new Response();
        $rsp->setCharset($req->charset);

        //context
        $this->m_context=new Context($req,$rsp);
        $this->m_context->setConfiguration($this->m_config);
    }

    /**
     *执行过滤
     * @param Context $context
     */
    private function doFilter()
    {
        //创建过滤链并执行过滤
        $filterChain=new FilterChain($this->m_filters);
        $filterChain->filter($this->m_context);
    }

    /**
     * 开始监听
     * @param Context $context
     */
    private function listenBefore()
    {
        //监听器开始
        foreach ($this->m_listeners as $listener){
            $listener->listenBefore($this->m_context);
        }
    }

    /**
     * 结束监听
     * @param Context $context
     */
    private function listenAfter()
    {
        //监听器结束
        //$_listeners=array_reverse($this->m_listeners);
        $_listeners=$this->m_listeners;
        foreach ($_listeners as $listener){
            $listener->listenAfter($this->m_context);
        }
    }

    /**
     * 响应输出
     */
    private function response()
    {
        $rsp=$this->m_context->getResponse();
        if(!$rsp->closed()){

            //发送状态码与头部
            if(!headers_sent()){
                http_response_code($rsp->getCode());
                $headers=$rsp->getHeaders();
                if(!array_key_exists("content-type", $headers) && !empty($rsp->getContentType())){
                    $headers["content-type"]=$rsp->getContentType();
                }
                foreach ($headers as $name => $value){
                    if(empty($value)){
                        header($name);
                    }else{
                        header($name.":".$value);
                    }
                }
            }

            //内容输出代理
            $out=$rsp->getOutput();
            if(!empty($out)){
                $out->output();
            }
        }
    }
}