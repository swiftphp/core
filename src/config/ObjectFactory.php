<?php
namespace swiftphp\core\config;

use swiftphp\core\BuiltInConst;

/**
 * 内置对象工厂
 * @author Tomix
 *
 */
class ObjectFactory implements IObjectFactory,IConfigurable
{
    /**
     * 对象配置键:对象id
     */
    private const OBJECT_CONFIG_KEY_ID="id";

    /**
     * 对象配置键:对象类名
     */
    private const OBJECT_CONFIG_KEY_CLASS="class";

    /**
     * 对象配置键:是否单例模式
     */
    private const OBJECT_CONFIG_KEY_SINGLETON="singleton";

    /**
     * 对象配置键:构造参数
     */
    private const OBJECT_CONFIG_KEY_CONSTRUCTOR_ARGS="constructor-args";

    /**
     * 对象配置:应用目录占位符
     */
    private const OBJECT_CONFIG_BASE_DIR_PLACE_HOLDER="\${baseDir}";

    /**
     * 对象配置:用户目录占位符
     */
    private const OBJECT_CONFIG_USER_DIR_PLACE_HOLDER="\${userDir}";

    /**
     * 对象配置:配置根目录占位符
     */
    private const OBJECT_CONFIG_CONFIG_DIR_PLACE_HOLDER="\${configDir}";


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
     * 注入配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }

    /**
     * 设置对象配置节点
     * @param string $value
     */
    public function setConfigSection($value)
    {
        $this->m_configSection=$value;
    }

    /**
     * 根据对象ID创建实例
     * @param string $objectId  对象ID
     */
    public function create($objectId)
    {
        //对象配置信息
        $objInfo=$this->getObjInfo($objectId);
        if($objInfo==null){
            throw new \Exception("Call to undefined object '".$objectId."'");
        }

        //如果单例模式且对象已经存在,直接返回
        if($objInfo->singleton && array_key_exists($objectId, $this->m_singletonObjMap)){
            return $this->m_singletonObjMap[$objectId];
        }

        //创建对象
        if(!class_exists($objInfo->class)){
            throw new \Exception("Class '".$objInfo->class."' not found");
        }
        $obj=$this->newInstance($objInfo->class,$objInfo->constructorArgs);

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
     * 根据类型名创建实例
     * @param string $class             类型名称
     * @param array $constructorArgs    构造参数(对于已配置的类型,该参数忽略)
     * @param bool $singleton           是否单例模式,默认为单例模式(对于已配置的类型,该参数忽略)
     */
    public function createByClass($class,$constructorArgs=[],$singleton=true)
    {
        //使用类型名代替ID创建对象,异常代表类型未配置,无需抛出
        try{
            $obj=$this->create($class);
            return $obj;
        }catch (\Exception $ex){}

        //单例模式下,尝试从缓存获取
        if($singleton && array_key_exists($class, $this->m_singletonObjMap)){
            $obj=$this->m_singletonObjMap[$class];
            if(!is_null($obj)){
                return $obj;
            }
        }

        //直接从类型名创建对象
        if(!class_exists($class)){
            throw new \Exception("Class '".$class."' not found");
        }
        $obj=$this->newInstance($class,$constructorArgs);

        //注入全局属性(全局属性只有值类型)
        foreach ($this->m_config->getConfigValues(BuiltInConst::$globalConfigSection) as $name=>$value){
            $this->setProperty($obj, $name, $value);
        }

        //通过配置接口注入配置实例
        if($obj instanceof IConfigurable){
            $obj->setConfiguration($this->m_config);
        }

        //如果为单态模式,则保存到缓存
        if($singleton){
            $this->m_singletonObjMap[$class]=$obj;
        }

        //返回对象
        return $obj;
    }

    /**
     * 创建对象实例
     * @param string $forClass       类名
     * @param array $constructorArgs 构造参数
     * @return object
     * @throws \Exception
     */
    public function newInstance($forClass,$constructorArgs=[])
    {
        $class = new \ReflectionClass($forClass);
        $constructor = $class->getConstructor();

        //无构造函数
        if($constructor==null){
            return new $forClass();
        }

        //有构造函数
        $parameters=$constructor->getParameters();
        $paramValues=[];
        for($i=0;$i<count($parameters);$i++){
            $parameter=$parameters[$i];
            $name=$parameter->name;
            $isDefaultValueAvailable=$parameter->isDefaultValueAvailable();
//             $allowsNull=$parameter->allowsNull();
//             $isArray=$parameter->isArray();
//             $isOptional=$parameter->isOptional();

            //参数取值:配置>默认
            if(array_key_exists($name, $constructorArgs)){
                //按参数名取值
                $paramValues[]=$this->getPropertyValue($constructorArgs[$name]);
            }else if(array_key_exists($i, $constructorArgs)){
                //按顺序取值
                $paramValues[]=$this->getPropertyValue($constructorArgs[$i]);
            }else if($isDefaultValueAvailable){
                $paramValues[]=$parameter->getDefaultValue();
            }else{
                throw new \Exception("Too few arguments to function ".$forClass."::__construct()");
            }
        }

        //创建实例
        $obj = $class->newInstanceArgs($paramValues);
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
            if(array_key_exists(self::OBJECT_CONFIG_KEY_CLASS, $cfg)){
                $info=new ObjectInfo();
                $info->class=$cfg[self::OBJECT_CONFIG_KEY_CLASS];
                $info->id=array_key_exists(self::OBJECT_CONFIG_KEY_ID, $cfg)?$cfg[self::OBJECT_CONFIG_KEY_ID]:$cfg[self::OBJECT_CONFIG_KEY_CLASS];

                //属性
                $info->propertyInfos=[];
                foreach ($cfg as $key=>$value){
                    if($key!=self::OBJECT_CONFIG_KEY_ID
                        && $key!=self::OBJECT_CONFIG_KEY_CLASS
                        && $key!=self::OBJECT_CONFIG_KEY_SINGLETON
                        && $key!=self::OBJECT_CONFIG_KEY_CONSTRUCTOR_ARGS){
                        $info->propertyInfos[$key]=$value;
                    }
                }

                //单例模式
                $info->singleton=true;
                if(array_key_exists(self::OBJECT_CONFIG_KEY_SINGLETON, $cfg)){
                    $val=$cfg[self::OBJECT_CONFIG_KEY_SINGLETON];
                    if($val=="0"||strtolower($val)=="false"){
                        $info->singleton=false;
                    }
                }

                //构造器参数
                $info->constructorArgs=[];
                if(array_key_exists(self::OBJECT_CONFIG_KEY_CONSTRUCTOR_ARGS, $cfg)){
                    $args=$cfg[self::OBJECT_CONFIG_KEY_CONSTRUCTOR_ARGS];
                    foreach ($args as $key=>$value){
                        $info->constructorArgs[$key]=$value;
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
                $key=$this->replacePlaceHolder($k);
                $values[$key]=$this->getPropertyValue($v);
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
        }else{
            $value=$this->replacePlaceHolder($value);
        }
        return $value;
    }

    /**
     * 替换占位符
     * @param string $value
     */
    private function replacePlaceHolder($value)
    {
        $value=str_replace(self::OBJECT_CONFIG_BASE_DIR_PLACE_HOLDER, $this->m_config->getBaseDir(), $value);
        $value=str_replace(self::OBJECT_CONFIG_USER_DIR_PLACE_HOLDER, $this->m_config->getUserDir(), $value);
        $value=str_replace(self::OBJECT_CONFIG_CONFIG_DIR_PLACE_HOLDER, $this->m_config->getConfigDir(), $value);
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

    /**
     * 构造器参数,键为参数名,值为参数描述
     * @var array
     */
    public $constructorArgs=[];
}

