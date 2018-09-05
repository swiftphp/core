<?php
namespace swiftphp\core\config\internal;

/**
 * 从PHP配置文件创建配置实例
 * @author Tomix
 *
 */
class PhpConfiguration extends AbstractConfiguration
{
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
        $this->m_configFiles=[$configFile];
        $this->m_configData = require $configFile;
    }
}

