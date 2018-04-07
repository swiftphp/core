<?php
namespace swiftphp\core\config;

/**
 * 配置工厂
 * @author Tomix
 *
 */
class ConfigurationFactory
{
    /**
     * 配置类静态实例数组
     * 键为配置文件，值为配置对象
     */
    private static $m_configs=[];

    /**
     * 持久化配置文件时的键
     * @var string
     */
    private static $m_configFilesKey="_xml-source-config-files";

    /**
     * 调试模式开关
     * @var string
     */
    private static $m_debug=false;

    /**
     * 调试模式开关
     * @param bool $value
     */
    public static function setDebug($value)
    {
        self::$m_debug=$value;
    }

    /**
     * 创建配置实例
     * @param string $configFile 入口配置文件名
     * @param string $baseDir    应用根目录
     * @param array  $extConfigs 附加扩展的配置(section,name,value形式的数组)
     * @return IConfiguration
     */
    public static function create($configFile,$baseDir="",$extConfigs=[])
    {
        //配置文件
        $pathInfo=pathinfo($configFile);
        $_configFile=$pathInfo["dirname"]."/".$pathInfo["filename"];
        $phpConfigFile=$_configFile.".php";
        $xmlConfigFile=$_configFile.".xml";
        $_configFile=file_exists($phpConfigFile)?$phpConfigFile:$xmlConfigFile;
        if(!file_exists($_configFile)){
            throw new \Exception("Fail to load configuration file!");
        }

        //如果缓存存在,则直接返回实例
        $configKey=$_configFile;
        if(array_key_exists($configKey, self::$m_configs)){
            return self::returnConfig(self::$m_configs[$configKey],$baseDir,$extConfigs);
        }

        //从php配置文件读取配置:如果php配置文件存在且修改时间大于所有的xml配置文件
        if(file_exists($phpConfigFile)){
            //从php读取配置
            $config=new PhpConfiguration($phpConfigFile);

            //如果xml配置文件不存在,则直接从php配置直接返回
            if(!file_exists($xmlConfigFile)){
                self::$m_configs[$configKey]=$config;
                return self::returnConfig($config,$baseDir,$extConfigs);
            }

            //如果xml配置文件存在,则要检查文件最后修改时间
            $phpTime=filemtime($phpConfigFile);
            $overrided=false;
            foreach ($config->getConfigValues(self::$m_configFilesKey) as $f){
                if(file_exists($f) && filemtime($f) > $phpTime){
                    $overrided=true;
                }
            }

            //php文件较新且非调试模式下
            if(!$overrided && !self::$m_debug){
                self::$m_configs[$configKey]=$config;
                return self::returnConfig($config,$baseDir,$extConfigs);
            }
        }

        //从xml配置读取配置,并持久化到php
        if(file_exists($xmlConfigFile)){
            $config=new XmlConfiguration($xmlConfigFile);
            self::$m_configs[$configKey]=$config;
            self::dump2PhpFile($config, $phpConfigFile);
        }

        //返回实例
        if(array_key_exists($configKey, self::$m_configs)){
            return self::returnConfig(self::$m_configs[$configKey],$baseDir,$extConfigs);
        }
        return null;
    }

    /**
     * 返回的配置实例
     * @param IConfiguration $config
     * @param string $baseDir
     * @param array $extConfigs
     * @return IConfiguration
     */
    private static function returnConfig(IConfiguration $config,$baseDir="",$extConfigs=[])
    {
        $config->setBaseDir($baseDir);
        if(!empty($extConfigs)){
            foreach ($extConfigs as $ext){
                $config->addConfigValue($ext["section"], $ext["name"], $ext["value"]);
            }
        }
        return $config;
    }

    /**
     * 把配置内容持久化成php文件
     * @param IConfiguration $config
     * @param string $file
     */
    private static function dump2PhpFile(IConfiguration $config,$file)
    {
        $values=$config->getAllValues();
        $values[self::$m_configFilesKey]=$config->getConfigFiles();
        $content=var_export($values, TRUE);
        $content="<?php\r\nreturn\r\n".$content.";";
        file_put_contents($file, $content);
    }

}