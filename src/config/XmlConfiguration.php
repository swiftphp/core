<?php
/*
 *xml配置解析逻辑
 *核心属性:name,value,ref,list="true"
 *name:有此属性的,以属性值作为配置键,否则以标签名作为键
 *value&ref:有其中一个属性标记为标签的结束;ref为引用类型的值
 *list="true"标签:标记为集合,即所有一级子元素为下标为整数的的数组,其属性标记的值失效.
 */

namespace swiftphp\core\config;

/**
 * 从xml文件创建配置实例
 * @author Administrator
 *
 */
class XmlConfiguration implements IConfiguration
{

    /**
     * 配置数据,数组(非name-value键值对数组)
     * @var array
     */
    private $m_configArray=[];

    /**
     * 配置入口文件
     * @var string
     */
    private $m_configFile;

    /**
     * 所有参与配置的文件
     * @var array
     */
    private $m_configFiles=[];

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
     * 构造函数,$configFile参数为配置xml文件名
     */
    public function __construct($configFile)
    {
        //文件是否存在
        if(!file_exists($configFile))
            throw new \Exception("Fail to load configuration file!");
        $this->m_configFile=$configFile;
        $this->m_configFiles[]=$configFile;

        //DOM档
        $doc=new \DOMDocument();
        $doc->load($configFile);

        //导入的xml合并到主文档
        $this->importXml($doc);

        //读取每个配置节数据
        foreach ($doc->documentElement->childNodes as $node){
            if(is_a($node,"DOMElement")){
                $nodeName=$node->nodeName;
                $name=$node->hasAttribute("name")?$node->getAttribute("name"):$nodeName;
                $this->m_configArray[$name]=$this->loadNode($node);
            }
        }
    }

    /**
     * 根据配置节和键值读取配置值
     * @param string $section
     * @param string $name
     * @return array
     */
    public function getConfigValue($section,$name)
    {
        if(array_key_exists($section, $this->m_configArray) && array_key_exists($name, $this->m_configArray[$section])){
            return $this->m_configArray[$section][$name];
        }
        return "";
    }

    /**
     * 根据配置节返回配置数据(键值对数组)
     * @param string $section
     * @return array
     */
    public function getConfigValues($section)
    {
        return $this->m_configArray[$section];
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
    public  function getConfigFiles()
    {
        return $this->m_configFiles;
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

    /**
     * 导入xml到节点处
     * @param \DOMDocument $doc
     */
    private function importXml(\DOMDocument $doc)
    {
        $importNodes=$doc->getElementsByTagName("import");
        for($i=$importNodes->length-1;$i>=0;$i--){
            $node = $importNodes->item($i);
            if($node->hasAttribute("file")){
                $file=$node->getAttribute("file");
                $path=rtrim(dirname($this->m_configFile),"/");
                $file=$path."/".ltrim($file,"/");
                if(file_exists($file)){
                    $_doc=new \DOMDocument();
                    $_doc->load($file);
                    $this->importXml($_doc);
                    $children=$_doc->documentElement->childNodes;
                    for($j=0;$j<$children->length;$j++){
                        $_node=$children->item($j);
                        if(is_a($_node,"DOMElement")){
                            $_node = $doc->importNode($_node, true);
                            $node->parentNode->insertBefore($_node,$node);
                        }
                    }
                    if(!in_array($file, $this->m_configFiles)){
                        $this->m_configFiles[]=$file;
                    }
                }
            }
            $node->parentNode->removeChild($node);
        }
    }


    /**
     * 读取节点内容
     * @param \DOMElement $node
     * @return string|unknown[]|string[]|NULL[]|unknown[][]|string[][]|NULL[][]
     */
    private function loadNode(\DOMElement $node)
    {
        //是否有value或ref属性,此两属性为值属性,标记结束
        if($node->hasAttribute("value")){
            return $node->getAttribute("value");
        }
        if($node->hasAttribute("ref")){
            return "ref:".$node->getAttribute("ref");
        }

        //返回值
        $returnValue=[];

        //是否有list属性
        $isList=false;
        if($node->hasAttribute("list")){
            $value=$node->getAttribute("list");
            if(strtolower($value)=="true" || $value=="1")
                $isList=true;
        }

        //非集合节点,读取属标记
        if(!$isList){
            $atts=$node->attributes;
            for($i=0;$i<$atts->length;$i++){
                $item=$atts->item($i);
                $attName=$item->nodeName;
                if($attName!="name" && $attName!="value" && $attName!="ref" && $attName!="list"){
                    $returnValue[$attName]=$item->nodeValue;
                }
            }
        }

        //如果有子节点时,继续读取
        if($node->hasChildNodes()){
            $children=$node->childNodes;
            for($i=0;$i<$children->length;$i++){
                $child=$children->item($i);
                if(is_a($child,"DOMElement")){
                    //是否有import标记,若有则并入到当前节点处
                    //取值
                    $value=$this->loadNode($child);
                    if($isList){
                        $returnValue[]=$value;
                    }else{
                        $name=$child->hasAttribute("name")?$child->getAttribute("name"):$child->nodeName;
                        $returnValue[$name]=$value;
                    }
                }
            }
        }



        //是否有import标记,若有则并入到当前节点处

        //返回值
        return $returnValue;
    }
}