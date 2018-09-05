<?php
namespace swiftphp\core\boot;

use swiftphp\core\system\IRunnable;
use swiftphp\core\config\ConfigurationFactory;

/**
 * 应用管理
 * @author Tomix
 *
 */
class Application
{
    /**
     * 执行一个可执行对象
     * @param IRunnable $target
     */
    public static function run(IRunnable $target)
    {
        $target->run();
    }

    /**
     * 静态的引导入口
     * @param string $configFile   主配置文件位置
     * @param string $baseDir       应用根目录(默认为配置入口文件所在目录)
     * @param string $userDir       用户根目录(默认与应用根目录相同)
     * @param array $extConfigs     附加扩展的配置(section,name,value形式的数组,默认为空)
     * @param string $containerId   容器对象ID(默认为:container)
     */
    public static function boot($configFile,$baseDir="",$userDir="",$extConfigs=[],$containerId="container")
    {
        //使用配置工厂创建配置实例
        $config=ConfigurationFactory::create($configFile,$baseDir,$userDir,$extConfigs);

        //获取对象工厂
        $objectFactory=$config->getObjectFactory();

        //使用对象工厂创建容器实例
        $container=$objectFactory->create($containerId);

        //执行一个容器实例
        self::run($container);
    }
}