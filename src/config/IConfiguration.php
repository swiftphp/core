<?php
namespace swiftphp\core\config;

/**
 * 配置接口
 * @author Tomix
 *
 */
interface IConfiguration
{
    /**
     * 获取配置值
     * @param 配置节点 $section
     * @param 配置键名 $name
     */
    function getConfigValue($section,$name);

    /**
     * 获取配置节点的所有值
     * @param 配置节点 $section
     */
    function getConfigValues($section);

    /**
     * 获取所有的配置
     */
    function getAllValues();

    /**
     * 当前入口配置文件
     */
    function getConfigFile();

    /**
     * 获取所有参与配置的文件
     */
    function getConfigFiles();

    /**
     * 当前入口配置文件所在目录
     */
    function getConfigDir();

    /**
     *获取当前应用根目录(未设置时应该返回配置入口所在目录)
     */
    function getBaseDir();

    /**
     * 设置当前应用根目录
     * @param string $value
     */
    function setBaseDir($value);

    /**
     * 获取用户目录(未设置时应该返回应用根目录)
     */
    function getUserDir();

    /**
     * 设置用户目录
     * @param string $value
     */
    function setUserDir($value);

    /**
     * 添加配置到当前实例
     * @param string $section
     * @param string $name
     * @param string $value
     */
    function addConfigValue($section,$name,$value);
}

