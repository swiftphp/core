<?php
namespace swiftphp\core\http;

use swiftphp\core\config\IConfiguration;
use swiftphp\core\system\ILogger;
use swiftphp\core\config\ConfigurationFactory;
use swiftphp\core\BuiltInConst;
use swiftphp\core\config\ObjectFactory;
use swiftphp\core\io\Path;

/**
 * 主入口容器
 * @author Tomix
 *
 */
class Container
{
    /**
     * 当前运行的配置实例
     * @var IConfiguration
     */
    private $m_config = null;

    /**
     * 容器所在配置节
     * @var string
     */
    private $m_configSection = "container";

    /**
     * 上下文
     * @var Context
     */
    private $m_context=null;

    /**
     * 所有过滤器
     * @var array
     */
    private $m_filters=[];

    /**
     * 所有监听器,原则上没有执行顺序
     * @var array
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
     * 容器构造
     * @param string $configFile    配置文件
     * @param string $baseDir       应用根目录(默认为配置入口文件所在目录)
     * @param array $extConfigs     附加扩展的配置(section,name,value形式的数组,默认为空)
     * @param string $configSection 容器配置节点(默认为:container)
     */
    public function __construct($configFile,$baseDir="",$extConfigs=[],$configSection="container")
    {
        $this->m_config=ConfigurationFactory::create($configFile,$baseDir,$extConfigs);
        $this->m_configSection=$configSection;
    }

    /**
     * 启动一个容器
     * @param Container $instance
     */
    public static function run(Container $instance)
    {
        $instance->load();
    }

    /**
     *容器执行主入口
     */
    private function load()
    {
        try{
            //初始化参数
            $this->initParams();

            //初始化上下文
            $this->initContext();

            //初始化过滤器
            $this->initFilters();

            //初始化监听器
            $this->initListeners();

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
     * 初始化参数
     */
    private function initParams()
    {
        //全局配置节点
        $configData = $this->m_config->getConfigValues(BuiltInConst::$globalConfigSection);

        //容器节点的配置值(相同属性名覆盖)
        $_configData=$this->m_config->getConfigValues($this->m_configSection);
        if(!empty($_configData) && array_key_exists("params", $_configData)){
            $_configData=$_configData["params"];
            foreach ($_configData as $key=>$value){
                $configData[$key]=$value;
            }
        }

        //注入到属性
        foreach ($configData as $name=>$value){
            $setter = "set" . ucfirst($name);
            if (method_exists($this, $setter)) {
                try{
                    if(strtolower($value)=="true"){
                        $value=true;
                    }else if(strtolower($value)=="false"){
                        $value=false;
                    }else if(strpos(strtolower($value), "ref:")===0){
                        $objId=substr($value, 4);
                        $value=ObjectFactory::getInstance($this->m_config)->create($objId);
                    }
                    $this->$setter($value);
                }catch (\Exception $ex){}
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
     * 初始化过滤器
     */
    private function initFilters()
    {
        $_configValues=$this->m_config->getConfigValues($this->m_configSection);
        if(!array_key_exists("filters", $_configValues)){
            return;
        }
        $filterConfigs=$_configValues["filters"];
        if(empty($filterConfigs)){
            return;
        }
        foreach ($filterConfigs as $filterConfig){
            $objId=$filterConfig["class"];
            $filter=ObjectFactory::getInstance($this->m_config)->create($objId);
            $this->m_filters[]=$filter;
        }
    }

    /**
     * 初始化监听器
     */
    private function initListeners()
    {
        $_configValues=$this->m_config->getConfigValues($this->m_configSection);
        if(!array_key_exists("listeners", $_configValues)){
            return;
        }
        $listenerConfigs=$_configValues["listeners"];
        if(empty($listenerConfigs)){
            return;
        }
        foreach ($listenerConfigs as $listenerConfig){
            $objId=$listenerConfig["class"];
            $filter=ObjectFactory::getInstance($this->m_config)->create($objId);
            $this->m_listeners[]=$filter;
        }
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