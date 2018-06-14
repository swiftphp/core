<?php
namespace swiftphp\core\data\orm\mapping;

use swiftphp\core\data\orm\mapping\DeleteJoin;

/**
 * 表模型
 * @author Tomix
 *
 */
class Table
{
    /**
     * 数据库表名
     * @var string
     */
    private $m_name;

    /**
     * 数据库表别名
     * @var string
     */
    private $m_alias;

    /**
     * 版本控制字段名
     * @var string
     */
    private $m_version;

    /**
     * 数据列集；键为列名，值为列模型
     * @var array
     */
    private $m_columns = [];

    /**
     * 数据列名集
     * @var array
     */
    private $m_columnNames=[];

    /**
     * 主键列名集
     * @var array
     */
    private $m_primaryKeys = [];

    /**
     * 唯一键列名集
     * @var array
     */
    private $m_uniqueKeys = [];

    /**
     * 自动递增列名集
     * @var array
     */
    private $m_incrementKeys = [];

    /**
     * 关联查询模型
     * @var SelectJoin
     */
    private $m_selectJoin = null;

    /**
     * 关联删除模型集
     * @var array
     */
    private $m_deleteJoins = [];

    /**
     * 一对多集合关联查询模型集.键为实体集合字段名，值为模型实例
     * @var array
     */
    private $m_oneToManyJoins = [];

    /**
     * 多对一模型集合
     * @var array
     */
    private $m_manyToOneJoins=[];

    /**
     * 数据库表名
     * @return string
     */
    public function getName()
    {
        return $this->m_name;
    }

    /**
     * 数据库表名
     * @param string $value
     */
    public function setName($value)
    {
        $this->m_name = $value;
    }

    /**
     * 数据库表别名
     * @return string
     */
    public function getAlias()
    {
        return $this->m_alias;
    }

    /**
     * 数据库表别名
     * @param string $value
     */
    public function setAlias($value)
    {
        $this->m_alias = $value;
    }

    /**
     * 版本控制字段名
     * @return string
     */
    public function getVersion()
    {
        return $this->m_version;
    }

    /**
     * 版本控制字段名
     * @param string $value
     */
    public function setVersion($value)
    {
        $this->m_version=$value;
    }

    /**
     * 数据列集；键为列名，值为列模型
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->m_columns;
    }

    /**
     * 数据列名集
     * @return array
     */
    public function getColumnNames()
    {
        return $this->m_columnNames;
    }

    /**
     * 主键列名集
     * @return array
     */
    public function getPrimaryKeys()
    {
        return $this->m_primaryKeys;
    }

    /**
     * 唯一键列名集
     * @return array
     */
    public function getUniqueKeys()
    {
        return $this->m_uniqueKeys;
    }

    /**
     * 自动递增列名集
     * @return array
     */
    public function getIncrementKeys()
    {
        return $this->m_incrementKeys;
    }

    /**
     * 添加一个列模型
     * @param string $name 列名
     * @param Column $value 数据列模型
     */
    public function addColumn($name, Column $value)
    {
        $this->m_columns[$name] = $value;
        if(!in_array($name, $this->m_columnNames)){
            $this->m_columnNames[]=$name;
        }
        if($value->getPrimary() && !in_array($name, $this->m_primaryKeys)){
            $this->m_primaryKeys[]=$name;
        }
        if($value->getUnique() && !in_array($name, $this->m_uniqueKeys)){
            $this->m_uniqueKeys[]=$name;
        }
        if($value->getIncrement() && !in_array($name, $this->m_incrementKeys)){
            $this->m_incrementKeys[]=$name;
        }
    }

    /**
     * 根据列名获取列模型
     * @param string $name
     * @return Column
     */
    public function getColumn($name)
    {
        if (array_key_exists($name, $this->m_columns))
            return $this->m_columns[$name];
        return null;
    }

    /**
     * 关联查询模型
     * @return SelectJoin
     */
    public function getSelectJoin()
    {
        return $this->m_selectJoin;
    }

    /**
     * 关联查询模型
     * @param SelectJoin $value
     */
    public function setSelectJoin(SelectJoin $value)
    {
        $this->m_selectJoin = $value;
    }

    /**
     * 一对多集合关联查询模型集.键为实体集合字段名，值为模型实例
     * @return OneToManyJoin[]
     */
    public function getOneToManyJoins()
    {
        return $this->m_oneToManyJoins;
    }

    /**
     * 一对多集合关联查询模型集.键为实体集合字段名，值为模型实例
     * @param array $value
     */
    public function setOneToManyJoins(array $value)
    {
        $this->m_oneToManyJoins = $value;
    }

    /**
     * 一对多集合关联查询模型集.键为实体集合字段名，值为模型实例
     * @param string $name
     * @param OneToManyJoin $value
     */
    public function addOneToManyJoin($name, OneToManyJoin $value)
    {
        $this->m_oneToManyJoins[$name] = $value;
    }

    /**
     * 根据属性名获取集合关联查询模型
     * @param string $name
     * @return OneToManyJoin
     */
    public function getOneToManyJoin($name)
    {
        if (array_key_exists($name, $this->m_oneToManyJoins))
            return $this->m_oneToManyJoins[$name];
            return null;
    }

    /**
     * 根据实体属性名移除集合关联型
     * @param string $name
     */
    public function removeOneToManyJoin($name)
    {
        if(array_key_exists($name, $this->m_oneToManyJoins)){
            unset($this->m_oneToManyJoins[$name]);
        }
    }

    /**
     * 多对一关联查询模型集.键为实体集合字段名，值为模型实例
     * @return ManyToOneJoin[]
     */
    public function getManyToOneJoins()
    {
        return $this->m_manyToOneJoins;
    }

    /**
     * 多对一关联查询模型集.键为实体集合字段名，值为模型实例
     * @param array $value
     */
    public function setManyToOneJoins(array $value)
    {
        $this->m_manyToOneJoins = $value;
    }

    /**
     * 多对一关联查询模型集.键为实体集合字段名，值为模型实例
     * @param string $name
     * @param ManyToOneJoin $value
     */
    public function addManyToOneJoin($name, ManyToOneJoin $value)
    {
        $this->m_manyToOneJoins[$name] = $value;
    }

    /**
     * 根据属性名获取多对一关联查询模型
     * @param string $name
     * @return ManyToOneJoin
     */
    public function getManyToOneJoin($name)
    {
        if (array_key_exists($name, $this->m_manyToOneJoins))
            return $this->m_manyToOneJoins[$name];
            return null;
    }

    /**
     * 根据实体属性名移除多对一关联型
     * @param string $name
     */
    public function removeManyToOneJoin($name)
    {
        if(array_key_exists($name, $this->m_manyToOneJoins)){
            unset($this->m_manyToOneJoins[$name]);
        }
    }

    /**
     * 关联删除模型集
     * @return DeleteJoin[]
     */
    public function getDeleteJoins()
    {
        return $this->m_deleteJoins;
    }

    /**
     * 关联删除模型集
     * @param array $value
     */
    public function setDeleteJoins(array $value)
    {
        $this->m_deleteJoins= $value;
    }

    /**
     * 添加关联删除模型
     * @param string $name 表名
     * @param DeleteJoin $value
     */
    public function addDeleteJoin($name, DeleteJoin $value)
    {
        $this->m_deleteJoins[$name] = $value;
    }

    /**
     * 根据关联表名获取关联删除模型
     * @param string $name
     * @return DeleteJoin
     */
    public function getDeleteJoin($name)
    {
        if (array_key_exists($name, $this->m_deleteJoins)){
            return $this->m_deleteJoins[$name];
        }
        return null;
    }

    /**
     * 据关联表名移除关联删除模型
     * @param unknown $name
     */
    public function removeDeleteJoin($name)
    {
        if(array_key_exists($name, $this->m_deleteJoins)){
            unset($this->m_deleteJoins[$name]);
        }
    }
}