<?php
namespace swiftphp\core\utils;

use swiftphp\core\system\ILogger;
use swiftphp\core\config\IConfigurable;
use swiftphp\core\config\IConfiguration;
use swiftphp\core\io\Path;
use swiftphp\core\BuiltInConst;
use swiftphp\core\io\File;

/**
 * 文件日志记录
 * @author Tomix
 *
 */
class FileLogger implements ILogger,IConfigurable
{
    /**
     * 配置实例
     * @var IConfiguration
     */
    private $m_config=null;

    /**
     * 注入配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }


    /**
     * 写入日志记录
     * @param $message	日志内容
     * @param $type		日志类型
     * @param $file		日志文件,写入文件时将会自动添加日期字串为后缀
     * @return void
     */
    public function log($message,$type="error",$prefix="err")
    {
        //msg
        $msg="time:".(string)date("Y-m-d H:i:s")."\r\n";
        $msg.="type:".$type."\r\n";
        $msg.="addr:".$_SERVER["REMOTE_ADDR"]."\r\n";
        $msg.="uri:".$_SERVER["REQUEST_URI"]."\r\n";
        $msg.="desc:".$message;
        $msg.="\r\n------------------------------------------------------------------------------------\r\n";

        //path
        $path=$this->getLogDir();
        $fn=Path::combinePath($path,str_replace(" ","_",$prefix)."_".(string)date("Ymd").".log");
        $fp=@fopen($fn,"ab");
        @fwrite($fp,$msg);
        @fclose($fp);
    }

    /**
     * 写入异常日志
     * @param \Exception $ex
     * @param string $prefix
     */
    public function logException(\Exception $ex,$prefix="ex")
    {
        $this->log($ex->getCode().":".$ex->getMessage()+"\r\n"+$ex->getTraceAsString(),get_class($ex),$prefix);
    }

    /**
     * 缓存目录
     * @return string
     */
    private function getLogDir()
    {
        $dir=__DIR__."/../../_logs/";
        if(!empty($this->m_config)){
            $dir=$this->m_config->getBaseDir();
            $_dir=$this->m_config->getConfigValue(BuiltInConst::$globalConfigSection, "logDir");
            if(!empty($_dir)){
                $dir=Path::combinePath($dir,$_dir);
            }else{
                $dir=Path::combinePath($dir, "_logs/");
            }
        }
        if(!file_exists($dir)){
            File::createDir($dir);
        }
        return $dir;
    }
}

