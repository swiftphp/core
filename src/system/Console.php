<?php
namespace swiftphp\core\system;

/**
 * Console输出
 * @author Tomix
 *
 */
class Console
{
    /**
     * 打印接口
     * @var IPrinter[]
     */
    private static $m_printers=[];

    /**
     * 添加打印接口
     * @param IPrinter $printer 接口实例
     * @param string $key              接口键
     */
    public static function addPrinter(IPrinter $printer,$key="")
    {
        if(empty($key)){
            $key=get_class($printer);
        }
        self::$m_printers[$key]=$printer;
    }

    /**
     * 根据键移除打印接口
     * @param string $key 接口键
     */
    public static function removePrinter($key)
    {
        if(array_key_exists($key, self::$m_printers)){
            unset(self::$m_printers[$key]);
        }
    }

    /**
     * 清空所有的打印接口
     */
    public static function clearPrinters()
    {
        self::$m_printers=[];
    }

    /**
     * 打印消息
     * @param string $msg        需要打印的消息
     * @param string $attachTime 是否添加时间到消息前缀
     * @param array $formatArgs  格式化参数
     */
    public static function print($msg, $attachTime = true, $formatArgs = [])
    {
        if(!empty($formatArgs)){
            for($i=0;$i<count($formatArgs);$i++){
                $msg=str_replace("{".$i.")", $formatArgs[$i], $msg);
            }
        }
        if($attachTime){
            $msg=DateTime::now()->toString().":".$msg;
        }
        foreach (self::$m_printers as $handler){
            $handler->print($msg);
        }
    }

    /**
     * 打印一行消息
     * @param string $msg        需要打印的消息
     * @param string $attachTime 是否添加时间到消息前缀
     * @param array $formatArgs  格式化参数
     */
    public static function printLine($msg, $attachTime = false, $formatArgs = [])
    {
        $msg.="\r\n";
        self::print($msg,$attachTime,$formatArgs);
    }

    /**
     * 打印异常消息
     * @param \Exception $ex
     */
    public static function printException(\Exception $ex)
    {
        $msg="code:".$ex->getCode()
            ."\r\nmessage:".$ex->getMessage()
            ."\r\ntrace:".$ex->getTraceAsString();
        self::printLine($msg);
    }
}
