<?php
namespace swiftphp\core\config;

use swiftphp\core\BuiltInConst;

/**
 * 对象工厂
 * @author Tomix
 *
 */
class ObjectFactory
{
    /**
     * 配置实例
     * @var IConfiguration
     */
    private $m_config;

    /**
     * 对象配置节点
     * @var string
     */
    private $m_configSection;

    /**
     * 对象信息配置,键为对象id
     * @var array
     */
    private $m_objectInfoMap=[];

    /**
     * 单例对象,键为对象id,值为对象
     * @var array
     */
    private $m_singletonObjMap=[];

    /**
     * 静态实例
     * @var array
     */
    private static $m_instanceMap=[];


    /**
     * 构造函数
     * @param IConfiguration $config 配置实例
     * @param string $configSection  对象配置节点
     */
    private function __construct(IConfiguration $config,$configSection="objects")
    {
        $this->m_config=$config;
        $this->m_configSection=$configSection;
    }

    /**
     * 创建工厂实例
     * @param IConfiguration $config 配置实例
     * @param string $configSection  对象配置节点
     * @return ObjectFactory
     */
    public static function getInstance(IConfiguration $config,$configSection="objects")
    {
        $configKey=$config->getConfigFile()."$".$configSection;
        if(!array_key_exists($configKey, self::$m_instanceMap)){
            self::$m_instanceMap[$configKey]=new ObjectFactory($config,$configSection);
        }
        return self::$m_instanceMap[$configKey];
    }

    /**
     * 根据对象ID创建对象
     * 执行过程:创建对象,注入全局配置,注入当前配置,注入配置实例
     * @param string $objectId 对象id(未配置id属性时,以类型名为id)
     */
    public function create($objectId)
    {
        //对象配置信息
        $objInfo=$this->getObjInfo($objectId);
        if($objInfo==null){
            return null;
        }

        //如果单例模式且对象已经存在,直接返回
        if($objInfo->singleton && array_key_exists($objectId, $this->m_singletonObjMap)){
             return $this->m_singletonObjMap[$objectId];
        }

        //创建对象
        if(!class_exists($objInfo->class)){
            return null;
        }
        $obj=new $objInfo->class();

        //注入全局属性(全局属性只有值类型)
        foreach ($this->m_config->getConfigValues(BuiltInConst::$globalConfigSection) as $name=>$value){
            $this->setProperty($obj, $name, $value);
        }

        //注入属性
        foreach ($objInfo->propertyInfos as $name=>$value){
            $this->setProperty($obj, $name, $value);
        }

        //如果为单态模式,则保存到缓存
        if($objInfo->singleton){
            $this->m_singletonObjMap[$objectId]=$obj;
        }

        //通过配置接口注入配置实例
        if($obj instanceof IConfigurable){
            $obj->setConfiguration($this->m_config);
        }

        //返回对象
        return $obj;

    }

    /**
     * 根据类型名创建对象
     * @param string $class         对象类型名
     * @param string $configSection 所在配置节点
     * @param $singleton            是否单例模式(如果有配置,则忽略此参数)
     */
    public function createByClass($class,$configSection="objects",$singleton=true)
    {
        //使用类型名代替ID创建对象
        $obj=$this->create($class,$configSection);
        if(!is_null($obj)){
            return $obj;
        }

        //创建对象
        if(!class_exists($class)){
            return null;
        }
        $obj=new $class();

        //注入全局属性(全局属性只有值类型)
        foreach ($this->m_config->getConfigValues(BuiltInConst::$globalConfigSection) as $name=>$value){
            $this->setProperty($obj, $name, $value);
        }

        //如果为单态模式,则保存到缓存
        if($singleton){
            $this->m_singletonObjMap[$class]=$obj;
        }

        //通过配置接口注入配置实例
        if($obj instanceof IConfigurable){
            $obj->setConfiguration($this->m_config);
        }

        //返回对象
        return $obj;
    }

    /**
     * 根据ID获取对象消息
     * @param string $objectId 对象ID
     * @return ObjectInfo
     */
    private function getObjInfo($objectId)
    {
        //映射不存在 ,则先创建
        if(empty($this->m_objectInfoMap)){
            $configData=$this->m_config->getConfigValues($this->m_configSection);
            $this->m_objectInfoMap=$this->loadObjMap($configData);
        }

        //对象信息
        if(array_key_exists($objectId, $this->m_objectInfoMap)){
            return $this->m_objectInfoMap[$objectId];
        }
        return null;
    }

    /**
     * 加载对象信息
     * @param array $configData
     */
    private function loadObjMap(array $configData)
    {
        $infos=[];
        foreach ($configData as $cfg){
            if(array_key_exists("class", $cfg)){
                $info=new ObjectInfo();
                $info->class=$cfg["class"];
                $info->id=array_key_exists("id", $cfg)?$cfg["id"]:$cfg["class"];

                //属性
                $info->propertyInfos=[];
                foreach ($cfg as $key=>$value){
                    if($key!="id" && $key!="class" && $key!="singleton"){
                        $info->propertyInfos[$key]=$value;
                    }
                }

                //单例模式
                $info->singleton=true;
                if(array_key_exists("singleton", $cfg)){
                    $val=$cfg["singleton"];
                    if($val=="0"||strtolower($val)=="false"){
                        $info->singleton=false;
                    }
                }

                $infos[$info->id]=$info;
            }
        }
        return $infos;
    }

    /**
     * 设置对象属性
     * @param object $obj 对象
     * @param string $name 属性名
     * @param mixed $valueInfo 属性值描述
     * @param bool $singleton 是否为单态模式调用
     */
    private function setProperty($obj,$name,$valueInfo)
    {
        //setter不存在,直接返回
        $setter = "set" . ucfirst($name);
        if (!method_exists($obj, $setter)) {
            return;
        }
        $obj->$setter($this->getPropertyValue($valueInfo));
    }

    /**
     * 根据属性值描述创建属性
     * @param mixed $valueInfo 属性值描述
     */
    private function getPropertyValue($valueInfo)
    {
        //属性值类型
        $value=$valueInfo;

        //如果值为数组,则递归创建元素值
        if(is_array($value)){
            $values=[];
            foreach ($value as $k => $v){
                $values[$k]=$this->getPropertyValue($v);
            }
            return $values;
        }

        //字符串类型的描述
        if(strtolower($value)=="true"){
            $value=true;
        }else if(strtolower($value)=="false"){
            $value=false;
        }else if(strpos(strtolower($value), "ref:")===0){
            //对象引用,递归创建对象
            $refObjId=substr($value, 4);
            $value=$this->create($refObjId);
        }
        return $value;
    }
}

/**
 * 对象配置信息
 * @author Tomix
 *
 */
class ObjectInfo
{
    /**
     * 对象id
     * @var string
     */
    public $id;

    /**
     * 是否单例模式
     * @var bool
     */
    public $singleton;

    /**
     * 对象类名
     * @var string
     */
    public $class;

    /**
     * 对象属性信息,键为属性名,值为属性描述
     * @var array
     */
    public $propertyInfos=[];
}
