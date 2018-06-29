<?php
namespace swiftphp\core\config;

use swiftphp\core\BuiltInConst;

/**
 * 配置抽象基类
 * @author Tomix
 *
 */
abstract class ConfigurationAbstract implements IConfiguration
{

    /**
     * 应用根目录
     * @var string
     */
    private $m_baseDir="";

    /**
     * 用户根目录
     * @var string
     */
    private $m_userDir="";

    /**
     * 对象工厂实例
     * @var IObjectFactory
     */
    private $m_objectFactory=null;

    /**
     * 获取配置值
     * @param 配置节点 $section
     * @param 配置键名 $name
     */
    public abstract function getConfigValue($section,$name);

    /**
     * 获取配置节点的所有值
     * @param 配置节点 $section
     */
    public abstract function getConfigValues($section);

    /**
     * 获取所有的配置
     */
    public abstract function getAllValues();

    /**
     * 当前入口配置文件
     */
    public abstract function getConfigFile();

    /**
     * 获取所有参与配置的文件
     */
    public abstract function getConfigFiles();

    /**
     * 当前入口配置文件所在目录
     */
    public abstract function getConfigDir();

    /**
     * 添加配置到当前实例
     * @param string $section
     * @param string $name
     * @param string $value
     */
    public abstract function addConfigValue($section,$name,$value);

    /**
     *获取当前应用根目录(若未设置应用根目录,则返回配置入口文件所在目录)
     */
    public function getBaseDir()
    {
        if(!empty($this->m_baseDir))
            return $this->m_baseDir;
            return $this->getConfigDir();
    }

    /**
     * 设置当前应用根目录
     */
    public function setBaseDir($value)
    {
        $this->m_baseDir=$value;
    }


    /**
     * 获取用户目录(未设置时应该返回应用根目录)
     */
    public function getUserDir()
    {
        if(!empty($this->m_userDir)){
            return $this->m_userDir;
        }
        return $this->getBaseDir();
    }

    /**
     * 设置用户目录
     * @param string $value
     */
    public function setUserDir($value)
    {
        $this->m_userDir=$value;
    }

    /**
     * 获取对象工厂实例
     * @return IObjectFactory
     */
    public function getObjectFactory()
    {
        if(is_null($this->m_objectFactory)){
            $config = $this->getConfigValues(BuiltInConst::$objectFactoryConfigSection);
            $class=$config["class"];
            if(!class_exists($class)){
                throw new \Exception("Class '".$class."' not found");
            }
            $obj=new $class();
            if($obj instanceof IConfigurable){
                $obj->setConfiguration($this);
            }

            //注入参数(全局参数与配置参数;只能为值类型)
            $params=$this->getConfigValues(BuiltInConst::$globalConfigSection);
            if(array_key_exists("params", $config)){
                foreach ($config["params"] as $name => $value){
                    $params[$name]=$value;
                }
            }
            foreach ($params as $name => $value){
                $setter="set".ucfirst($name);
                if(method_exists($obj, $setter)){
                    $obj->$setter($value);
                }
            }
            $this->m_objectFactory=$obj;
        }
        return $this->m_objectFactory;
    }
}

