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
abstract class Config implements IConfigurable
{
    /**
     * ORM配置文件
     * @var string
     */
    protected $m_mapping_file;

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
     * 应用根目录
     * @var string
     */
    protected $m_baseDir="";

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
     * 配置实例
     * @var IConfiguration
     */
    protected $m_config=null;

    /**
     * 数据库实例
     * @var IDatabase
     */
    protected $m_database;

    /**
     * ORM映射表集
     * @var array
     */
    protected $m_tables = [];

    /**
     * 是否调试状态
     * @param bool $value
     */
    public function setDebug($value)
    {
        $this->m_debug = $value;
    }

    /**
     * 设置应用根目录
     * @param string $value
     */
    public function setBaseDir($value)
    {
        $this->m_baseDir=$value;
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
     * 注入配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }

    /**
     * ORM配置文件
     * @param string $value
     */
    public function setMappingFile($value)
    {
        $this->m_mapping_file = $value;
    }


    /**
     * 数据库实例
     * @param IDatabase $value
     */
    public function setDatabase(IDatabase$value)
    {
        $this->m_database = $value;
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
     * @return array
     */
    public function getTables()
    {
        return $this->m_tables;
    }

    /**
     * 加载配置
     */
    public function load()
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
     *
     * @param string|object $class
     * @return Table
     */
    public function getTable($class)
    {
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
    public abstract function mappingColumns(&$table);

    /**
     * 映射数据库字段类型
     * @param string $sqlType 数据库字段类型
     * @return string
     */
    public abstract function mappingType($sqlType);

    /**
     * ORM映射
     * @return \swiftphp\core\data\orm\mapping\Table[]
     */
    protected function loadMapping()
    {
        //mapping file
        $mappingFile=$this->m_mapping_file;
        if(!empty($this->m_baseDir)){
            $mappingFile=Path::combinePath($this->m_baseDir, $mappingFile);
        }else if(!empty($this->m_config)){
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
            $this->mappingSelect($table, $obj);

            // mapping one to many
            $this->mappingOneToManys($table, $class, $obj);

            // mapping one to many
            $this->mappingManyToOne($table, $class,$namespace, $obj);

            // mapping dels
            $this->mappingDels($table, $obj);

            // add to mapping table set
            $mapping[$class] = $table;
        }
        return $mapping;
    }

    /**
     *
     * @param table $table
     * @param string $xml
     */
    protected function mappingSelect($table, $xml)
    {
        // set nodes
        $obj = $xml->getElementsByTagName("select");
        if ($obj->length > 0) {
            $obj = $obj->item(0);
            $cols = $obj->hasAttribute("columns") ? $obj->getAttribute("columns") : $table->getAlias() . ".*";
            $select = new SelectJoin();
            $select->setColumns($cols);

            // joins
            $_objs = $obj->getElementsByTagName("join");
            foreach ($_objs as $_obj) {
                $joinTable = $_obj->getAttribute("table");
                $alias = $_obj->hasAttribute("alias") ? $_obj->getAttribute("alias") : "_" . $joinTable;
                $on = $_obj->getAttribute("on");
                $join = new Join();
                $join->setTable($joinTable);
                $join->setAlias($alias);
                $join->setOn($on);
                $select->addJoin($joinTable, $join);
            }
            $table->setSelectJoin($select);
        }
    }

    /**
     *
     * @param table $table
     * @param string $class
     * @param string $xml
     * @return void
     */
    protected function mappingOneToManys($table, $class, $xml)
    {
        $objs = $xml->getElementsByTagName("one-to-many");
        if ($objs->length > 0) {
            $objs = $objs->item(0)->getElementsByTagName("set");
            foreach ($objs as $obj) {
                $name = $obj->getAttribute("name");
                if (property_exists($class, $name)) {
                    $cols = $obj->hasAttribute("columns") ? $obj->getAttribute("columns") : $obj->getAttribute("alias") . ".*";
                    $sync = $obj->getAttribute("sync"); // 该标签放在主关联表表示同步insert,update
                    $order = $obj->getAttribute("order");

                    $set = new OneToManyJoin();
                    $set->setColumns($cols);
                    $set->setSync($sync);
                    $set->setOrder($order);

                    // 第一个join元素可以设置为属性
                    $tbl = $obj->getAttribute("table");
                    if (! empty($tbl)) {
                        $alias = $obj->hasAttribute("alias") ? $obj->getAttribute("alias") : "_" . $obj->getAttribute("table");
                        $on = $obj->getAttribute("on");
                        $join = new Join();
                        $join->setTable($tbl);
                        $join->setAlias($alias);
                        $join->setOn($on);
                        $set->addJoin($tbl, $join);
                    }

                    // joins
                    $_objs = $obj->getElementsByTagName("join");
                    foreach ($_objs as $_obj) {
                        $tbl = $_obj->getAttribute("table");
                        $alias = $_obj->hasAttribute("alias") ? $_obj->getAttribute("alias") : "_" . $tbl;
                        $on = $_obj->getAttribute("on");
                        $join = new Join();
                        $join->setTable($tbl);
                        $join->setAlias($alias);
                        $join->setOn($on);
                        $set->addJoin($tbl, $join);
                    }
                    $table->addOneToManyJoin($name, $set);
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
    protected function mappingManyToOne($table,$class,$namespace,$xml)
    {
        //配置:<entity name="category" class="ProdCategory" table="pro_category" alias="c" on="c.id=p.category_id" columns="*" />
        //其中table可选,没有配置时使用类名从配置查询;columns只对查询有效
        $objs = $xml->getElementsByTagName("many-to-one");
        if ($objs->length > 0) {
            $objs = $objs->item(0)->getElementsByTagName("entity");
            foreach ($objs as $obj) {
                $name = $obj->getAttribute("name");
                if (property_exists($class, $name)) {
                    $_class=$obj->getAttribute("class");
                    $_class = trim($namespace, "\\") . "\\" . trim($_class, "\\");
                    $cols = $obj->hasAttribute("columns") ? $obj->getAttribute("columns") : "*";
                    $tbl=$obj->hasAttribute("table") ? $obj->getAttribute("table"):"";
                    $alias = $obj->hasAttribute("alias") ? $obj->getAttribute("alias"):"";
                    if(empty($alias)&&!empty($tbl)){
                        $alias="_".$tbl;
                    }
                    $on = $obj->getAttribute("on");

                    //add join to table
                    $bean=new ManyToOneJoin();
                    $bean->setClass($_class);
                    $bean->setColumns($cols);
                    $bean->setTable($tbl);
                    $bean->setAlias($alias);
                    $bean->setOn($on);
                    $table->addManyToOneJoin($name,$bean);
                }
            }
        }
    }

    /**
     *
     * @param table $table
     * @param string $xml
     */
    protected function mappingDels($table, $xml)
    {
        // set nodes
        $objs = $xml->getElementsByTagName("dels");
        if ($objs->length > 0) {
            $objs = $objs->item(0)->getElementsByTagName("del");
            foreach ($objs as $obj) {
                $del = new DeleteJoin();

                // 第一个join可以设置成属性
                $tbl = $obj->getAttribute("table");
                if (! empty($tbl)) {
                    $alias = $obj->hasAttribute("alias") ? $obj->getAttribute("alias") : "_" . $obj->getAttribute("table");
                    $on = $obj->getAttribute("on");
                    $join = new Join();
                    $join->setAlias($alias);
                    $join->setTable($tbl);
                    $join->setOn($on);
                    $del->addJoin($tbl, $join);
                }

                // joins
                $_objs = $obj->getElementsByTagName("join");
                foreach ($_objs as $_obj) {
                    $tbl = $_obj->getAttribute("table");
                    $alias = $_obj->hasAttribute("alias") ? $_obj->getAttribute("alias") : "_" . $tbl;
                    $on = $_obj->getAttribute("on");
                    $join = new Join();
                    $join->setAlias($alias);
                    $join->setTable($tbl);
                    $join->setOn($on);
                    $del->addJoin($tbl, $join);
                }
                $table->addDeleteJoin($tbl, $del);
            }
        }
    }
}