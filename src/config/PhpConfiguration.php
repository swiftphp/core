<?php
namespace swiftphp\core\config;

/**
 * 从PHP配置文件创建配置实例
 * @author Tomix
 *
 */
class PhpConfiguration implements IConfiguration
{

    /**
     * 配置数据,数组(非name-value键值对数组)
     * @var array
     */
    private $m_configArray;

    /**
     * 配置入口文件
     * @var string
     */
    private $m_configFile;

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
     * 构造函数,$configFile参数为配置文件名全名
     */
    public function __construct($configFile)
    {
        //文件是否存在
        if (!file_exists($configFile)) {
            throw new \Exception("Fail to load configuration file!");
        }
        $this->m_configFile=$configFile;
        $this->m_configArray = require $configFile;
    }

    /**
     * 根据配置节和键值读取配置值
     * @param string $section
     * @param string $name
     * @return array
     */
    public function getConfigValue($section, $name)
    {
        $values=$this->getConfigValues($section);
        if(array_key_exists($name, $values))
            return $values[$name];

        return null;
    }

    /**
     * 根据配置节返回配置数据(键值对数组)
     * @param string $section
     * @return array
     */
    public function getConfigValues($section)
    {
        if(array_key_exists($section, $this->m_configArray))
            return $this->m_configArray[$section];
        return [];
    }

    /**
     * 获取所有的配置
     */
    public function getAllValues()
    {
        return $this->m_configArray;
    }

    /**
     * 当前入口配置文件
     */
    public function getConfigFile()
    {
        return $this->m_configFile;
    }

    /**
     * 获取所有参与配置的文件
     */
    public function getConfigFiles()
    {
        return [$this->m_configFile];
    }

    /**
     * 当前入口配置文件所在目录
     */
    public function getConfigDir()
    {
        return dirname($this->m_configFile);
    }

    /**
     *获取当前应用根目录(若未设置应用根目录,则返回配置入口文件所在目录)
     */
    public function getBaseDir()
    {
        if(!empty($this->m_baseDir)){
            return $this->m_baseDir;
        }
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
     * 添加配置到当前实例
     * @param string $section
     * @param string $name
     * @param string $value
     */
    public function addConfigValue($section,$name,$value)
    {
        if(!array_key_exists($section, $this->m_configArray)){
            $this->m_configArray[$section]=[];
        }
        $this->m_configArray[$section][$name]=$value;

    }
}

