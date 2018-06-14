<?php
namespace swiftphp\core\data\orm\mapping;

use swiftphp\core\data\db\IDatabase;
use swiftphp\core\system\ILogger;
use swiftphp\core\system\ICacher;
use swiftphp\core\config\IConfigurable;
use swiftphp\core\config\IConfiguration;
use swiftphp\core\io\Path;

/**
 * ORM配置抽象类
 *
 * @author Tomix
 */
abstract class Config implements IConfig, IConfigurable
{
    /**
     * ORM配置文件
     * @var string
     */
    protected $m_mapping_file;

    /**
     * 数据库实例
     * @var IDatabase
     */
    protected $m_database;

    /**
     * 配置实例
     * @var IConfiguration
     */
    protected $m_config=null;

    /**
     * 是否启用缓存
     * 测试时使用cache时速度快100倍以上
     * @var bool
     */
    protected $m_cacheable = true;

    /**
     * 是否调试状态
     * @var bool
     */
    protected $m_debug = false;

    /**
     * 缓存管理器
     * @var ICacher
     */
    protected $m_cacher = null;

    /**
     * 日志记录器
     * @var ILogger
     */
    protected $m_logger = null;

    /**
     * ORM映射表集
     * @var array
     */
    protected $m_tables = [];

    /**
     * 设置ORM配置文件(相对于应用根目录)
     * @param string $value
     */
    public function setMappingFile($value)
    {
        $this->m_mapping_file = $value;
    }

    /**
     * 设置数据库实例
     * @param IDatabase $value
     */
    public function setDatabase(IDatabase$value)
    {
        $this->m_database = $value;
    }

    /**
     * 设置配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }

    /**
     * 是否调试状态
     * @param bool $value
     */
    public function setDebug($value)
    {
        $this->m_debug = $value;
    }

    /**
     * 是否启用缓存
     * @param bool $value
     */
    public function setCacheable($value)
    {
        $this->m_cacheable = $value;
    }

    /**
     * 缓存管理器
     * @param ICacher $value
     */
    public function setCacher(ICacher $value)
    {
        $this->m_cacher = $value;
    }

    /**
     * 日志管理器
     * @param ILogger $value
     */
    public function setLogger(ILogger $value)
    {
        $this->m_logger = $value;
    }

    /**
     * 获取ORM映射的所有表名
     * @return Table[]
     */
    public function getTables()
    {
        $this->load();
        return $this->m_tables;
    }

    /**
     *
     * @param string|object $class
     * @return Table
     */
    public function getTable($class)
    {
        $this->load();
        if (is_object($class))
            $class = get_class($class);
        if (array_key_exists($class, $this->m_tables))
            return $this->m_tables[$class];
        return null;
    }

    /**
     * 映射数据库字段抽象方法,由具体数据库配置实例实现
     * @param Table $table
     */
    public abstract function mappingColumns(Table &$table);

    /**
     * 映射数据库字段类型
     * @param string $sqlType 数据库字段类型
     * @return string
     */
    protected abstract function mappingType($sqlType);

    /**
     * 加载配置
     */
    protected function load()
    {
        if (empty($this->m_tables)) {
            if ($this->m_cacheable && ! empty($this->m_cacher)) {
                $cacheKey = md5($this->m_mapping_file);
                $cache = $this->m_cacher->get($cacheKey);
                if (empty($cache) || (file_exists($this->m_mapping_file) &&  filemtime($this->m_mapping_file) > $this->m_cacher->getCacheTime($cacheKey))) {
                    $this->m_tables = $this->loadMapping();
                    $this->m_cacher->set($cacheKey, $this->m_tables);
                } else {
                    $this->m_tables = $cache;
                }
            } else {
                $this->m_tables = $this->loadMapping();
            }
        }
    }

    /**
     * ORM映射
     * @return \swiftphp\core\data\orm\mapping\Table[]
     */
    protected function loadMapping()
    {
        //mapping file
        $mappingFile=$this->m_mapping_file;
        if(!empty($this->m_config)){
            $mappingFile=Path::combinePath($this->m_config->getBaseDir(), $mappingFile);
        }

        // load from xml file
        $xmlDoc = new \DOMDocument();
        $xmlDoc->load($mappingFile);

        // namespace
        $namespace = $xmlDoc->documentElement->attributes->getNamedItem("namespace")->nodeValue;

        // class nodes
        $mapping = [];
        $objs = $xmlDoc->getElementsByTagName("class");
        foreach ($objs as $obj) {
            //配置属性: <class name="SysRegistry" table="sys_registry" alias="r" version="version" />
            $class = $obj->getAttribute("name");
            $class = trim($namespace, "\\") . "\\" . trim($class, "\\");
            $tableName = $obj->getAttribute("table");
            $alias = $obj->hasAttribute("alias") ? $obj->getAttribute("alias") : "_" . $tableName;
            $version = $obj->hasAttribute("version") ? $obj->getAttribute("version") : "";

            //表模型
            $table = new Table();
            $table->setName($tableName);
            $table->setAlias($alias);
            $table->setVersion($version);

            // mapping columns
            $this->mappingColumns($table);

            // mapping select
            $this->mappingSelectJoin($table, $obj);

            // mapping dels
            $this->mappingDeleteJoins($table, $obj);

            // mapping one to many
            $this->mappingOneToManyJoins($table, $class, $namespace, $obj);

            // mapping one to many
            $this->mappingManyToOneJoins($table, $class,$namespace, $obj);

            // add to mapping table set
            $mapping[$class] = $table;

//             var_dump($table);
//             exit;
        }
        return $mapping;
    }

    /**
     *
     * @param table $table
     * @param string $xml
     */
    protected function mappingSelectJoin($table, $xml)
    {
        // set nodes
        $obj = $xml->getElementsByTagName("select");
        if ($obj->length > 0) {
            $obj = $obj->item(0);
            $cols = $obj->hasAttribute("columns") ? $obj->getAttribute("columns") : $table->getAlias() . ".*";
            $select = new SelectJoin();
            $select->setColumns($cols);
            $this->mappingJoins($select, $obj);
            $table->setSelectJoin($select);
        }
    }

    /**
     * 映射关联删除
     * 1.关联删除的主表必须放在<del>标识内的第一个join或放在<del>标记属性
     * 2.删除顺序必须按从子向父级配置
     * @param table $table
     * @param string $xml
     */
    protected function mappingDeleteJoins($table, $xml)
    {
        // set nodes
        $objs = $xml->getElementsByTagName("dels");
        if ($objs->length > 0) {
            $objs = $objs->item(0)->getElementsByTagName("del");
            foreach ($objs as $obj) {
                $del = new DeleteJoin();
                $this->mappingJoins($del, $obj);
                $tbl = $obj->getAttribute("table");
                if(empty($tbl)){
                    $joins=$del->getJoins();
                    if(count($joins)>0){
                        $tbl=array_values($joins)[0]->getTable();
                    }
                }
                if(!empty($tbl)){
                    $table->addDeleteJoin($tbl, $del);
                }
            }
        }
    }

    /**
     * 映射一对多
     * 多个join必须按照关联逻辑顺序配置
     * @param table $table
     * @param string $class
     * @param string $xml
     * @return void
     */
    protected function mappingOneToManyJoins($table, $class,$namespace, $xml)
    {
        $objs = $xml->getElementsByTagName("one-to-many");
        if ($objs->length > 0) {
            $objs = $objs->item(0)->getElementsByTagName("many");
            foreach ($objs as $obj) {
                $name = $obj->getAttribute("name");
                if (property_exists($class, $name)) {
                    $many = new OneToManyJoin();
                    $this->mappingJoins($many, $obj);

                    //关联主表别名,如果没有配置到属性,则从第一个join取
                    $alias=$obj->getAttribute("alias");
                    if(empty($alias)){
                        $joins=$many->getJoins();
                        if(count($joins)>0){
                            $alias=array_values($joins)[0]->getAlias();
                        }
                    }

                    //多方的类型名,用于同步加载数据时实例化
                    $_class=$obj->getAttribute("class");
                    $_class = trim($namespace, "\\") . "\\" . trim($_class, "\\");
                    $many->setClass($_class);

                    //查询列,同步与排序
                    $sync = $obj->getAttribute("sync"); // 该标签放在主关联表表示同步insert,update
                    $order = $obj->getAttribute("order");
                    $cols = $obj->hasAttribute("columns") ? $obj->getAttribute("columns") : $alias . ".*";
                    $many->setSync($sync);
                    $many->setOrder($order);
                    $many->setColumns($cols);

                    //mapping joins
                    $table->addOneToManyJoin($name, $many);
                }
            }
        }
    }

    /**
     * 多对一映射
     * @param Table $table      表对象
     * @param string $class     主模型类全名
     * @param string $namespace 类命名空间前缀
     * @param string $xml       节点xml文档
     */
    protected function mappingManyToOneJoins($table,$class,$namespace,$xml)
    {
        $objs = $xml->getElementsByTagName("many-to-one");
        if ($objs->length > 0) {
            $objs = $objs->item(0)->getElementsByTagName("one");
            foreach ($objs as $obj) {
                $name = $obj->getAttribute("name");
                if (property_exists($class, $name)) {
                    $many2one = new ManyToOneJoin();
                    $this->mappingJoins($many2one, $obj);

                    //关联主表别名,如果没有配置到属性,则从第一个join取
                    $alias=$obj->getAttribute("alias");
                    if(empty($alias)){
                        $joins=$many2one->getJoins();
                        if(count($joins)>0){
                            $alias=array_values($joins)[0]->getAlias();
                        }
                    }

                    //一方的类型名,用于同步加载数据时实例化
                    $_class=$obj->getAttribute("class");
                    $_class = trim($namespace, "\\") . "\\" . trim($_class, "\\");
                    $many2one->setClass($_class);

                    //查询列集
                    $cols = $obj->hasAttribute("columns") ? $obj->getAttribute("columns") : $alias . ".*";
                    $many2one->setColumns($cols);

                    //mapping joins
                    $table->addManyToOneJoin($name, $many2one);
                }
            }
        }
    }

    /**
     * 映射join关联集合
     * @param JoinCollection $joins
     * @param string $outerXml
     */
    private function mappingJoins(JoinCollection $joinCollection,$xml)
    {
        // 第一个join元素可以设置为属性
        $tbl = $xml->getAttribute("table");
        if (! empty($tbl)) {
            $alias = $xml->hasAttribute("alias") ? $xml->getAttribute("alias") : "_" . $xml->getAttribute("table");
            $on = $xml->getAttribute("on");
            $join = new Join();
            $join->setTable($tbl);
            $join->setAlias($alias);
            $join->setOn($on);
            $joinCollection->addJoin($tbl, $join);
        }

        // joins
        $_objs = $xml->getElementsByTagName("join");
        foreach ($_objs as $_obj) {
            $tbl = $_obj->getAttribute("table");
            $alias = $_obj->hasAttribute("alias") ? $_obj->getAttribute("alias") : "_" . $tbl;
            $on = $_obj->getAttribute("on");
            $join = new Join();
            $join->setTable($tbl);
            $join->setAlias($alias);
            $join->setOn($on);
            $joinCollection->addJoin($tbl, $join);
        }
    }
}