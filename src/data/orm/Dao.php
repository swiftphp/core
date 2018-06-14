<?php
namespace swiftphp\core\data\orm;

use swiftphp\core\config\IConfigurable;
use swiftphp\core\data\db\IDatabase;
use swiftphp\core\system\ILogger;
use swiftphp\core\config\IConfiguration;
use swiftphp\core\utils\StringUtil;
use swiftphp\core\data\orm\mapping\Table;
use swiftphp\core\data\orm\mapping\Join;
use swiftphp\core\data\orm\mapping\IConfig;
use swiftphp\core\utils\SecurityUtil;
use swiftphp\core\data\orm\mapping\ManyToOneJoin;

/**
 * 数据DAO
 * @author Tomix
 *
 */
class Dao implements IDao, IConfigurable
{
    /**
     * 数据源
     * @var IDatabase
     */
    private $m_database;

    /**
     * 当前ORM配置
     * @var IConfig
     */
    private $m_ormConfig=null;

    /**
     * 配置实例
     * @var IConfiguration
     */
    private $m_config=null;

    /**
     * 是否调试状态
     * @var bool
     */
    private $m_debug=false;

    /**
     * 日志
     * @var ILogger
     */
    private $m_logger=null;

    /**
     * 打印SQL语句
     * @var string
     */
    private $m_showSql=false;

    /**
     * 事务状态：true表示状态已挂起
     * @var bool
     */
    private $m_transactionStatus=false;

    /**
     * 注入数据源描述
     * @param IDatabase $value
     */
    public function setDatabase(IDatabase $value)
    {
        $this->m_database=$value;
    }

    /**
     * 获取数据访问对象
     * @return IDatabase
     */
    public function getDatabase()
    {
        return $this->m_database;
    }

    /**
     * ORM配置
     * @param IConfig $value
     */
    public function setOrmConfig(IConfig $value)
    {
        $this->m_ormConfig=$value;
    }

    /**
     * 获取当前ORMs配置
     * @return IConfig
     */
    public function getOrmConfig()
    {
        return $this->m_ormConfig;
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
     * 是否调试状态
     * @param bool $value
     */
    public function setDebug($value)
    {
        $this->m_debug=$value;
    }

    /**
     * 注入日志记录器
     * @param ILogger $value
     */
    public function setLogger(ILogger $value)
    {
        $this->m_logger=$value;
    }

    /**
     * 是否打印SQL语句
     * @return bool
     */
    public function getShowSql()
    {
        return $this->m_showSql;
    }

    /**
     * 是否打印SQL语句
     * @param bool $value
     */
    public function setShowSql($value)
    {
        $this->m_showSql = $value;
    }

    /**
     * 获取最后一次产生的异常
     * @return \Exception
     */
    public function getException()
    {
        return $this->getDatabase()->getException();
    }

    /**
     * 获取当前的事务状态
     * @return boolean
     */
    public function getTransactionStatus()
    {
        return $this->m_transactionStatus;
    }

    /**
     * 开始事务
     */
    public function beginTransaction()
    {
        $this->getDatabase()->begin();
        $this->m_transactionStatus = true;
    }

    /**
     * 提交事务
     */
    public function commitTransaction()
    {
        $this->getDatabase()->commit();
        $this->m_transactionStatus = false;
    }

    /**
     * 回滚事务
     */
    public function rollbackTransaction()
    {
        $this->getDatabase()->rollback();
        $this->m_transactionStatus = false;
    }

    /**
     * 载入实体默认值
     * @param object $model
     */
    public function loadDefaultValue($model)
    {
        $cols=$this->getOrmConfig()->getTable($model)->getColumns();
        foreach ($cols as $name => $col) {
            $field=EntityUtil::mapModelField($model, $name);
            if (!empty($field)) {
                EntityUtil::setFieldValue($model, $field, $col->getDefault());
            }
        }
    }

    /**
     * 加载数据
     * @param object $model 实体对象
     * @param string|array $fields 查询字段,用实体属性名表示,留空表示按主键主段查询
     * @param bool $sync
     * @throws \Exception
     * @return boolean
     */
    public function load($model, $fields = null, $sync = true,$aliasOneToManyToEntity=false)
    {
        //表模型
        $tableObj = $this->getOrmConfig()->getTable($model);
        $table = $tableObj->getName();
        $primaryKeys = $tableObj->getPrimaryKeys();
        $columnNames=$tableObj->getColumnNames();

        //查询字段,用实体属性名表示,留空表示按主键主段查询
        if (! empty($fields) && ! is_array($fields)){
            $fields = [$fields];
        }

        //拼接过滤表达式
        $filter = "";
        $params=[];
        if (is_array($fields) && count($fields) > 0) {
            //按指定字段查询
            foreach ($fields as $field) {
                if(!EntityUtil::fieldExists($model,$field)){
                    throw new \Exception("实体'" . get_class($model) . "'不存在属性'" . $field. "'");
                }
                $dbField=$field;
                if(!in_array($dbField, $columnNames)){
                    $dbField=StringUtil::toUnderlineString($field);
                }
                if(!in_array($dbField, $columnNames)){
                    throw new \Exception("字段'" . $field . "'不属性于表'" . $table . "'");
                }
                if ($filter != ""){
                    $filter .= " AND ";
                }
                $filter .= $dbField. "=:".$dbField;
                $params[$dbField]=EntityUtil::getFieldValue($model, $field);
            }
        } else {
            //按主键查询
            foreach ($primaryKeys as $keyField) {
                $field=EntityUtil::mapModelField($model, $keyField);
                if(empty($field)){
                    throw new \Exception("实体'".get_class($model)."'不存在主键标识'".$keyField."'");
                }
                if ($filter != ""){
                    $filter .= " AND ";
                }
                $filter .= $keyField . "=:" . $keyField;
                $params[$keyField]=EntityUtil::getFieldValue($model, $field);
            }
        }
        $sql = "SELECT * FROM " . $table . "";
        $sql .= " WHERE " . $filter;

        $reader=$this->getDatabase()->reader($sql,$params);
        if ($reader && is_null($this->getDatabase()->getException())) {
            foreach ($reader as $fieldName=> $value) {
                $field=EntityUtil::mapModelField($model, $fieldName);
                $field=EntityUtil::mapModelField($model, $fieldName);
                if(!empty($field)){
                    EntityUtil::setFieldValue($model, $field, $value);
                }
            }
        } else {
            return false;
        }

        //加载集合属性(关联表)与多对一属性,映射到二维数组,键为字段名或映射驼峰命名字段名
        if ($sync) {
            //加载多对一
            $this->loadManyToOneJoins($model, $tableObj);

            //加载一对多
            $this->loadOneToManyJoins($model, $tableObj,$aliasOneToManyToEntity);
        }
        if (is_null($this->getException())) {
            return true;
        } else {
            throw $this->getException();
        }
    }

    /**
     * (已测试)
     * 插入一条记录,成功则返回记录ID,失败返回false
     * @param Object $model
     * @param bool $sync
     */
    public function insert($model, $sync = true)
    {
        if (! $this->m_transactionStatus){
            $this->getDatabase()->begin();
        }

        //表对象
        $tableObj = $this->getOrmConfig()->getTable($model);
        $table = $tableObj->getName();
        $alias = $tableObj->getAlias();
        $sets = $tableObj->getOneToManyJoins();
        $primaryKeys = $tableObj->getPrimaryKeys();
        $incrementKeys = $tableObj->getIncrementKeys();
        $columns = $tableObj->getColumnNames();

        // 插入主记录
        $fields = "";
        $values = "";
        $params=[];
        foreach ($columns as $columnName) {
            $fieldName=EntityUtil::mapModelField($model, $columnName);
            if (! in_array($columnName, $incrementKeys) && !empty($fieldName)) {
                if ($fields != ""){
                    $fields .= ",";
                }
                $fields .= $columnName;
                if ($values != ""){
                    $values .= ",";
                }
                $values .= ":".$columnName;

                // 字段值
                $fieldValue = EntityUtil::getFieldValue($model, $fieldName);
                $params[$columnName]=$fieldValue;
            }
        }
        $sql = "INSERT INTO " . $table . " ({0}) VALUES ({1})";
        $sql = str_replace("{0}", $fields, $sql);
        $sql = str_replace("{1}", $values, $sql);

        //执行操作
        $this->getDatabase()->execute($sql,$params);
        $insertId = $this->getDatabase()->getInsertId();

        //提取插入ID重新赋值到模型属性(非自动递增的ID不需要处理)
        if (! empty($insertId)) {
            foreach ($primaryKeys as $pKey) {
                $propName=EntityUtil::mapModelField($model, $pKey);
                if (in_array($pKey, $incrementKeys) && !empty($propName)) {
                    EntityUtil::setFieldValue($model, $propName, $insertId);
                    break;
                }
            }
        }

        //插入关联一对多数据
        if ($sync) {
            foreach ($sets as $name => $set) {
                if ($set->getSync()) {
                    //关联集
                    $joins = $set->getJoins();

                    //主要关联表
                    $join = null;
                    foreach ($joins as $j) {
                        $join = $j; // 第一个为关联主表
                        break;
                    }
                    $_table = $join->getTable();
                    $_alias = $join->getAlias();
                    $_on = $join->getOn();

                    //映射表字段
                    $_tableObj = new Table();
                    $_tableObj->setName($_table);
                    $this->getOrmConfig()->mappingColumns($_tableObj);

                    // 从关联条件分解外键与主表字段
                    $_key = null;
                    $_fkey = null;
                    $key_arr = explode("=", $_on);
                    foreach ($key_arr as $key_str) {
                        $_key_arr = explode(".", $key_str);
                        $_tbl = $_key_arr[0];
                        if ($_tbl == $table || $_tbl == $alias){
                            $_key = $_key_arr[1];
                        }
                        if ($_tbl == $_table || $_tbl == $_alias){
                            $_fkey = $_key_arr[1];
                        }
                    }
                    if($_key == null || $_fkey == null){
                        continue;
                    }
                    $keyField=EntityUtil::mapModelField($model, $_key);//映射到实体属性字段
                    if(empty($keyField)){
                        continue;
                    }
                    // foreach rows
                    $many=EntityUtil::getFieldValue($model, $name);
                    if(empty($many)||!is_array($many)){
                        continue;
                    }
                    foreach ($many as $row) {
                        $fields = "";
                        $values = "";
                        $params=[];
                        foreach ($_tableObj->getColumns() as $col) {
                            $dbField = $col->getName();//表字段名
                            $field="";
                            if(is_object($row)){
                                $field=EntityUtil::mapModelField($row, $dbField);//属性名
                            }else if(is_array($row)){
                                $field=EntityUtil::mapArrayKey($row, $dbField);//数组键
                            }
                            $value = null;
                            if ($dbField== $_fkey) {
                                $value = EntityUtil::getFieldValue($model, $keyField);
                            } elseif (!empty($field)) {
                                if(is_object($row)){
                                    $value=EntityUtil::getFieldValue($row, $field);
                                }else if(is_array($row)){
                                    $value = $row[$field];
                                }
                            }

                            //拼装SQL
                            if (($dbField== $_fkey || !empty($field)) && !in_array($dbField, $_tableObj->getIncrementKeys()) &&  !empty($value)) {

                                // 字段
                                if ($fields != ""){
                                    $fields .= ",";
                                }
                                $fields .= $dbField;

                                // 字段值
                                if ($values != ""){
                                    $values .= ",";
                                }
                                $values.=":".$dbField;
                                $params[$dbField]=$value;
                            }
                        }
                        $sql = "INSERT INTO " . $_table . " ({0}) VALUES ({1})";
                        $sql = str_replace("{0}", $fields, $sql);
                        $sql = str_replace("{1}", $values, $sql);
                        $this->getDatabase()->execute($sql,$params);
//                         echo $sql."\r\n";
//                         $this->getDatabase()->rollback();
//                         exit;
                    }
                }
            }
        }
        if (is_null($this->getException())) {
            if (!$this->m_transactionStatus){
                $this->getDatabase()->commit();
            }
            $this->load($model, null, $sync); // 重新加载记录
            return ($insertId ? $insertId : true);
        } else {
            if (! $this->m_transactionStatus){
                $this->getDatabase()->rollback();
            }
            throw $this->getException();
        }
    }

    /**
     * 更新一条记录(未完成)
     * @param object $model 数据实体
     * @param string $sync  是否同步到关联表
     * @param bool $humpFieldFirst 更新关联表时，是否以驼峰命名的字段优先取值.
     * @param string $forVersion 新版本号:表设置了版本号字段且新版本非空时,实现版本号校验(乐观锁)
     * @return boolean
     */
    public function update($model, $sync = true,$humpFieldFirst=true, $forVersion="")
    {
        //表对象
        $tableObj = $this->getOrmConfig()->getTable($model);
        $table = $tableObj->getName();
        $alias = $tableObj->getAlias();
        $properties = $tableObj->getColumnNames();
        $primaryKeys = $tableObj->getPrimaryKeys();
        $incrementKeys = $tableObj->getIncrementKeys();

        //事务
        if (! $this->m_transactionStatus){
            $this->getDatabase()->begin();
        }

        // 更新主表
        $filter = "";
        $update = "";
        $params=[];
        $keyParams=[];
        $versionField=$tableObj->getVersion();//乐观锁版本控制字段
        foreach ($properties as $fieldName) {
            if($fieldName==$versionField){
                //排除版本控制字段
                continue;
            }
            $field=EntityUtil::mapModelField($model, $fieldName);
            if(empty($field)){
                if(in_array($fieldName, $primaryKeys)){
                    throw new \Exception("实体'".get_class($model)."'不包含主键字段.".$fieldName."'");
                }else{
                    continue;
                }
            }
            if (in_array($fieldName, $primaryKeys)) {
                // 主键字段
                if ($filter != ""){
                    $filter .= " AND ";
                }
                $filter .= $alias . "." . $fieldName . "=:" . $fieldName;
                $keyValue=EntityUtil::getFieldValue($model, $field);
                $params[$fieldName]=$keyValue;
                $keyParams[$fieldName]=$keyValue;
            } elseif (! in_array($fieldName, $incrementKeys)) {
                // 非主键字段
                if ($update != ""){
                    $update .= ",";
                }
                $update .= $alias . "." . $fieldName . "=:".$fieldName;

                // 字段值
                $fieldValue=EntityUtil::getFieldValue($model, $field);
                $params[$fieldName]=$fieldValue;
            }
        }
        $sql = "UPDATE " . $table . " " . $alias . " SET {0} WHERE {1}";

        //版本控制实现乐观锁
        $_filter=$filter;//添加了版本控制的过滤
        $modelVerField=EntityUtil::mapModelField($model, $versionField);
        if(!empty($forVersion) && !empty($versionField) && !empty($modelVerField)){
            if ($_filter != ""){
                $_filter .= " AND ";
            }
            //$filter .= $alias . "." . $fieldName . "=:" . $fieldName;
            $_filter .= "(".$alias.".".$versionField."=:".$versionField." OR ".$alias.".".$versionField." IS NULL)";
            $params[$versionField]=EntityUtil::getFieldValue($model, $modelVerField);
            if ($update != ""){
                $update .= ",";
            }
            $update .= $alias . "." .$versionField."=:_".$versionField;
            $params["_".$versionField]=$forVersion;
        }

        //执行操作
        $sql = str_replace("{0}", $update, $sql);
        $sql = str_replace("{1}", $_filter, $sql);
        $_rows=$this->getDatabase()->execute($sql,$params);
        if($_rows==0 && !empty($forVersion)){
            if (! $this->m_transactionStatus){
                $this->getDatabase()->rollback();
            }
            throw new \Exception("数据保存失败,可能原因为数据版本冲突",0);//异常信息以后修改
        }

        // 更新关联表
        if ($sync) {
            $sets = $tableObj->getOneToManyJoins();
            foreach ($sets as $name => $set) {
                if ($set->getSync()) {
                    $joins = $set->getJoins();
                    $join = null;
                    foreach ($joins as $j) {
                        $join = $j; // 第一个为关联主表
                        break;
                    }
                    $_table = $join->getTable();
                    $_alias = $join->getAlias();
                    $_on = $join->getOn();

                    $_tableObj = new Table();
                    $_tableObj->setName($_table);
                    $this->getOrmConfig()->mappingColumns($_tableObj);

                    // 从关联条件分解外键与主表字段
                    $_key = null; // 主表提供的键
                    $_fkey = null; // 从表提供的外键
                    $key_arr = explode("=", $_on);
                    foreach ($key_arr as $key_str) {
                        $_key_arr = explode(".", $key_str);
                        $_tbl = $_key_arr[0];

                        // 主表提供的键
                        if ($_tbl == $table || $_tbl == $alias){
                            $_key = $_key_arr[1];
                        }
                        // 关联表提供的外键
                        if ($_tbl == $_table || $_tbl == $_alias){
                            $_fkey = $_key_arr[1];
                        }
                    }
                    if ($_key == null || $_fkey == null){
                        continue;
                    }
                    $keyField=EntityUtil::mapModelField($model, $_key);//映射到实体属性字段
                    if(empty($keyField)){
                        continue;
                    }

                    // keys
                    $pks = "";
                    foreach ($_tableObj->getPrimaryKeys() as $pk) {
                        if ($pks != ""){
                            $pks .= ",";
                        }
                        $pks .= $_alias . "." . $pk;
                    }

                    // existing rows
                    $sql = "SELECT " . $pks . " FROM " . $_table . " " . $_alias . " JOIN " . $table . " " . $alias . " ON " . $_on . " WHERE " . $filter;
                    $rs = $this->getDatabase()->query($sql,$keyParams);

                    // index rows by keys
                    $existMap = [];
                    foreach ($rs as $row) {
                        $key_str = "";
                        foreach ($_tableObj->getPrimaryKeys() as $pk) {
                            if ($key_str != ""){
                                $key_str .= ",";
                            }
                            $key_str .= $row[$pk];
                        }
                        $existMap[$key_str] = $row;
                    }

                    // scan this prop for set
                    $many=EntityUtil::getFieldValue($model, $name);
                    foreach ($many as $row) {
                        // $_key=null;//主表提供的键
                        // $_fkey=null;//从表提供的外键

                        //从表的外键从主表赋值,程序更新时不需要设置外键值
                        //$row[$_fkey] = EntityUtil::getFieldValue($model, $keyField);
                        $_fkeyValue=EntityUtil::getFieldValue($model, $keyField);
                        EntityUtil::setDataFieldValue($row, $_fkey, $_fkeyValue);

                        // key string
                        $key_str = "";
                        foreach ($_tableObj->getPrimaryKeys() as $pk) {
                            $pk=EntityUtil::mapDataField($row, $pk);
                            if(!empty($pk)){
                                if ($key_str != ""){
                                    $key_str .= ",";
                                }
                                $key_str .= EntityUtil::getDataFieldValue($row, $pk);
                            }
                        }

                        // if row exists
                        if (!empty($key_str) && array_key_exists($key_str, $existMap)) {
                            $_row = $existMap[$key_str];//旧记录
                            if (count($_tableObj->getColumns()) > count($_tableObj->getPrimaryKeys())) {
                                // update
                                $_filter = "";
                                $update = "";
                                $params=[];
                                foreach ($_tableObj->getColumns() as $col) {
                                    $field = $col->getName();//数据库字段名
                                    $indexName=$field;
                                    if($indexName==$_fkey){
                                        continue;
                                    }

                                    //驼峰属性优先取值
                                    if($humpFieldFirst){
                                        $indexName=StringUtil::toHumpString($field);
                                    }

                                    //如果驼峰属性不存在,则重新映射
//                                     if(!array_key_exists($indexName, $row)){
//                                         $indexName=EntityUtil::mapDataField($row, $field);
//                                     }
                                    if(!EntityUtil::dataFieldExists($row, $indexName)){
                                        $indexName=EntityUtil::mapDataField($row, $field);
                                    }
                                    //属性不存在,则放弃该字段的更新
                                    if(empty($indexName) || !EntityUtil::dataFieldExists($row, $indexName)){
                                        continue;
                                    }
                                    if (in_array($field, $_tableObj->getPrimaryKeys())) {

                                        // 主键字段
                                        if ($_filter != ""){
                                            $_filter .= " AND ";
                                        }
                                        $_filter .= $_alias . "." . $field . "=:" . $field;
                                        $params[$field]=EntityUtil::getDataFieldValue($_row, $field);
                                    } else if (! in_array($field, $_tableObj->getIncrementKeys())) {
                                        // 非主键,非自动递增字段
                                        if ($update != "")
                                            $update .= ",";
                                            $update .= $_alias . "." . $field . "=:".$field;
                                            $params[$field]=EntityUtil::getDataFieldValue($row, $indexName);
                                    }
                                }
                                $sql = "UPDATE " . $_table . " " . $_alias . " SET {0} WHERE {1};";
                                $sql = str_replace("{0}", $update, $sql);
                                $sql = str_replace("{1}", $_filter, $sql);
                                if(!empty($update) && !empty($_filter)){
                                    $this->getDatabase()->execute($sql,$params);
                                }
                            }

                            // remove this row
                            unset($existMap[$key_str]);
                        }else {
                            //if row not exists,then insert
                            $fields = "";
                            $values = "";
                            $params=[];
                            foreach ($_tableObj->getColumns() as $col) {
                                //字段名与映射字段名
                                $field = $col->getName();
                                $indexName=$field;
                                if($humpFieldFirst){
                                    $indexName=StringUtil::toHumpString($field);
                                }
                                if(!EntityUtil::dataFieldExists($row, $indexName)){
                                    $indexName=EntityUtil::mapDataField($row, $field);
                                }
                                if(empty($indexName) || !EntityUtil::dataFieldExists($row, $indexName)){
                                    continue;
                                }

                                //字段值
                                $value = null;
                                if ($field == $_fkey) {
                                    //$value = $model->$keyField;//从表的外键从实体主键取值
                                    $value=EntityUtil::getFieldValue($model, $keyField);
                                } else{
                                    //$value = $row[$indexName];
                                    $value=EntityUtil::getDataFieldValue($row, $indexName);
                                }

                                if (! in_array($field, $_tableObj->getIncrementKeys()) && $value != null) {
                                    // 字段
                                    if ($fields != ""){
                                        $fields .= ",";
                                    }
                                    $fields .= $field;

                                    // 字段值
                                    if ($values != ""){
                                        $values .= ",";
                                    }
                                    $values.=":".$field;
                                    $params[$field]=$value;
                                }
                            }
                            $sql = "INSERT INTO " . $_table . " ({0}) VALUES ({1});";
                            $sql = str_replace("{0}", $fields, $sql);
                            $sql = str_replace("{1}", $values, $sql);
                            $this->getDatabase()->execute($sql,$params);
                        }
                    }

                    // delete not existing row
                    foreach ($existMap as $row) {
                        $_filter = "";
                        $_params=[];
                        foreach ($_tableObj->getPrimaryKeys() as $pk) {
                            if ($_filter != ""){
                                $_filter .= " AND ";
                            }
                            //$_filter .= $pk . "='" . $row[$pk] . "'";
                            $_filter .= $pk . "=:" . $pk;
                            $_params[$pk]= $row[$pk];
                        }
                        $sql = "DELETE FROM " . $_table . " WHERE " . $_filter . ";";
                        $this->getDatabase()->execute($sql,$_params);
                    }
                }
            }
        }
        if (is_null($this->getException())) {
            if (! $this->m_transactionStatus){
                $this->getDatabase()->commit();
            }
            $this->load($model, null, $sync); // 重新加载记录
            return true;
        } else {
            if (! $this->m_transactionStatus){
                $this->getDatabase()->rollback();
            }
            throw $this->getException();
        }
    }

    /**
     * 删除一条记录,成功返回影响记录数1,失败返回false
     * @param object $model
     */
    public function delete($model)
    {
        if (! $this->m_transactionStatus){
            $this->getDatabase()->begin();
        }

        $tableObj = $this->getOrmConfig()->getTable($model);
        $table = $tableObj->getName();
        $alias = $tableObj->getAlias();
        $dels = $tableObj->getDeleteJoins();
        $primaryKeys = $tableObj->getPrimaryKeys();

        // 删除过滤
        $filter = "";
        $params=[];
        foreach ($primaryKeys as $keyField) {
            $field=EntityUtil::mapModelField($model, $keyField);
            if(empty($field)){
                throw new \Exception("实体'".get_class($model)."'不包含主键字段'".$keyField."'");
            }
            if ($filter != ""){
                $filter .= " AND ";
            }
            $filter .= $alias . "." . $keyField . "=:" . $keyField;
            $params[$keyField]=EntityUtil::getFieldValue($model, $field);
        }

        // sql语句组
        $sql_array = [];

        // 删除关联表记录(dels标记,sets标记不能同步删除)
        foreach ($dels as $del) {
            $sql = "DELETE {0} FROM " . $table . " " . $alias;
            $joins = $del->getJoins();
            $tbls = array_keys($joins);
            $_alias = "";
            for ($i = count($tbls) - 1; $i >= 0; $i --) {
                $tbl = $tbls[$i];
                $join = $del->getJoin($tbl);
                $_table = $join->getTable();
                $_alias = $join->getAlias();
                $_on = $join->getOn();
                $sql .= " JOIN " . $_table . " " . $_alias . " ON " . $_on;
            }
            $sql .= " WHERE " . $filter;
            $sql = str_replace("{0}", $_alias, $sql);
            $sql_array[] = $sql;
        }

        // 删除主表记录
        $sql = "DELETE " . $alias . " FROM " . $table . " " . $alias . " WHERE " . $filter;
        $sql_array[] = $sql;
        $intRows = - 1;
//         var_dump($sql_array);
//         exit;
        foreach ($sql_array as $sql) {
            $intRows = $this->getDatabase()->execute($sql,$params);
            if ($intRows === false){
                break;
            }
        }

        if (is_null($this->getException()) && $intRows !== false) {
            if (! $this->m_transactionStatus){
                $this->getDatabase()->commit();
            }
            return $intRows;
        } else {
            if (! $this->m_transactionStatus){
                $this->getDatabase()->rollback();
            }
            throw $this->getException();
        }
    }

    /**
     * 批量删除
     * @param string $modelClass
     * @param string $filter
     */
    public function deletes($modelClass, $filter = "",array $params=[])
    {
        $table = $this->getOrmConfig()->getTable($modelClass)->getName();
        $sql = "DELETE FROM " . $table;
        if (!empty($filter)){
            $filter=$this->mapSqlExpression($modelClass, $filter);
            $sql .= " WHERE " . $filter;
        }
        return $this->getDatabase()->execute($sql,$params);
    }

    /**
     * 数据查询
     * @param string $modelClass        实体类型名
     * @param string $filter            过滤表达式
     * @param array $params             输入参数
     * @param string $sort              排序表达式
     * @param number $offset            起始记录号
     * @param number $length            返回记录数
     * @param string $aliasToEntityMap  是否返回实体数组,否则返回二维键值对数组(默认false)
     * @param string $withManyToOne     是否返回多对一的上级数据(默认true)
     * @param string $toHumpFields      是否复制到驼峰命名的字段(默认true);返回实体数组时无效
     * @param string $fields            查询主表字段名,指定字段名可以简化返回结果,提高查询效率
     */
    public function select($modelClass, $filter = "",array $params=[], $sort = "", $offset = 0, $length = -1,$aliasToEntityMap=false,$withManyToOne=true,$toHumpFields=true, $fields = "")
    {
        // mapping config
        $tableObj = $this->getOrmConfig()->getTable($modelClass);

        //返回字段名
        if(empty($fields)){
            $fields="*";
        }
        //$fields=$this->mapSqlExpression($modelClass, $fields);
        $fields=$this->addAliasToFieldExp($fields, $tableObj->getAlias());

        //join sql
        $allJoins=[];

        //过滤表达式
        $joins = $this->compileManyToOneExp($tableObj->getManyToOneJoins(), $filter);
        foreach ($joins as $_alias => $join){
            if(!array_key_exists($_alias, $allJoins)){
                $allJoins[$_alias]=$join;
            }
        }

        //排序表达式
        $joins = $this->compileManyToOneExp($tableObj->getManyToOneJoins(), $sort);
        foreach ($joins as $_alias => $join){
            if(!array_key_exists($_alias, $allJoins)){
                $allJoins[$_alias]=$join;
            }
        }

        //是否返回多对一的上级数据(默认true)
        $manyToOneFieldMap=[];
        if($withManyToOne){
            $joins=$this->compileManyToOneColumns($tableObj->getManyToOneJoins(), $manyToOneFieldMap);
            foreach ($joins as $_alias => $join){
                if(!array_key_exists($_alias, $allJoins)){
                    $allJoins[$_alias]=$join;
                }
            }
            //列信息集:键为数据库真实返回的列名,值("target":一方名称,"field":一方字段名,"query":查询输入的列名)
            if(!empty($manyToOneFieldMap)){
                foreach ($manyToOneFieldMap as $oneQueryField){
                    $fields.=",".$oneQueryField["query"];
                }
            }
        }

        //sql
        $lineEnd=$this->m_debug?"\r\n":"";
        $sql = "SELECT ".$fields." FROM " . $tableObj->getName()." ".$tableObj->getAlias().$lineEnd;

        //joins
        foreach ($allJoins as $join){
            $sql.=" LEFT JOIN ".$join->getTable()." ".$join->getAlias()." ON ".$join->getOn().$lineEnd;
        }

        //过滤
        if (!empty($filter)){
            $sql .= " WHERE " . $filter.$lineEnd;
        }

        //排序
        if(!empty($sort)){
            $sql.=" ORDER BY ".$sort.$lineEnd;
        }

        //查询
        $sql=$this->mapSqlExpression($modelClass, $sql,true);
        $rs = $this->getDatabase()->query($sql,$params, $offset, $length);

//         var_dump($rs);
//         exit;
        if(empty($rs)){
            return $rs;
        }
//         * @param string $aliasToEntityMap  是否返回实体数组,否则返回二维键值对数组(默认false)
//         * @param string $withManyToOne     是否返回多对一的上级数据(默认true)
//         * @param string $toHumpFields      是否复制到驼峰命名的字段(默认true);返回实体数组时无效

        $lines=[];
        $keys=array_keys($rs[0]);
        for($i=0;$i<count($rs);$i++){
            $line=$rs[$i];
            if($aliasToEntityMap){
                //装配到实体数组
                $entity=new $modelClass();
                $many2one=[];
                $entityFieldMap=[];
                $oneFieldMap=[];
                foreach ($line as $key=>$value){
                    if(array_key_exists($key, $manyToOneFieldMap)){
                        //many2one的字段
                        $map=$manyToOneFieldMap[$key];
                        $name=$map["target"];
                        $fd=$map["field"];
                        $class=$map["class"];
                        if(!array_key_exists($name, $many2one)){
                            $many2one[$name]=new $class();
                            $oneFieldMap[$name]=[];
                        }
                        $oneObject=$many2one[$name];
                        $fieldMap=$oneFieldMap[$name];
                        if(!array_key_exists($fd, $fieldMap)){
                            $fieldMap[$fd]=EntityUtil::mapModelField($oneObject, $fd);
                            $oneFieldMap[$name]=$fieldMap;
                        }
                        $field=$fieldMap[$fd];
                        EntityUtil::setFieldValue($oneObject, $field, $value);
                        unset($line[$key]);
                    }else{
                        if(!array_key_exists($key, $entityFieldMap)){
                            $entityFieldMap[$key]=EntityUtil::mapModelField($entity, $key);
                        }
                        $field=$entityFieldMap[$key];
                        EntityUtil::setFieldValue($entity, $field, $value);
                    }
                }
                foreach ($many2one as $name=>$oneObj){
                    EntityUtil::setFieldValue($entity, $name, $oneObj);
                }
                $lines[$i]=$entity;
            }else{
                //装配到二维数组
                $many2one=[];
                foreach ($line as $key=>$value){
                    if(array_key_exists($key, $manyToOneFieldMap)){
                        //many2one的字段
                        $map=$manyToOneFieldMap[$key];
                        $name=$map["target"];
                        $fd=$map["field"];
                        if(!array_key_exists($name, $many2one)){
                            $many2one[$name]=[];
                        }
                        $many2one[$name][$fd]=$value;
                        if($toHumpFields){
                            if(!is_numeric($fd)){
                                $fd=StringUtil::toHumpString($fd);
                                if(!array_key_exists($fd, $many2one[$name])){
                                    $many2one[$name][$fd]=$value;
                                }
                            }
                        }
                        unset($line[$key]);
                    }else if($toHumpFields){
                        ////转换为驼峰命名的列
                        if(!is_numeric($key)){
                            $key=StringUtil::toHumpString($key);
                            if(!array_key_exists($key, $keys)){
                                $line[$key]=$value;
                            }
                        }
                    }
                }
                if(!empty($many2one)){
                    $line=array_merge($line,$many2one);
                }
                $lines[$i]=$line;
            }
        }

        return $lines;
    }

    /**
     * 查询计数
     * @param unknown $modelClass
     * @param string $filter
     * @param string $joinFilter
     * @return array
     */
    public function count($modelClass, $filter = "",array $params=[])
    {
        // mapping config
        $tableObj = $this->getOrmConfig()->getTable($modelClass);
        $table = $tableObj->getName();
        $sql="SELECT COUNT(*) FROM ".$table." ".$tableObj->getAlias();

        //一对多的过滤
        $joins=$this->compileManyToOneExp($tableObj->getManyToOneJoins(), $filter);
        if(!empty($joins)){
            foreach ($joins as $_alias => $_join){
                $sql.=" JOIN ".$_join->getTable()." ".$_alias." ON ".$_join->getOn()."\r\n";
            }
        }
        if(trim($filter) != ""){
            $sql .= " WHERE ".$filter;
        }
        $sql=$this->mapSqlExpression($modelClass,$sql,true);
        return $this->getDatabase()->scalar($sql,$params);
    }

    /**
     * 数据聚合统计(函数名必须与对应的数据库一致)
     * @param string|object $modelClass  模型类名或实例
     * @param string $filter             过滤表达式
     * @param array $params              输入参数
     * @param string $sort               排序表达式
     * @param array $funcMap             统计函数,键为函数名,值为统计字段(值为数组时,第一个元素为统计字段,第二个为返回字段);至少包含一个元素
     * @param array $groupFields         分组统计字段,可选
     */
    public function group($modelClass,$filter = "",array $params=[],$sort="",$funcMap=[],$groupFields=[])
    {
        if(empty($funcMap)){
            return false;
        }
        $table=$this->getOrmConfig()->getTable($modelClass);
        $sql="";
        $groupBy="";
        foreach ($funcMap as $func=>$fd){
            if(!empty($sql)){
                $sql.=",";
            }
            $_fd=$fd;
            $_returnFd=$fd;
            if(is_array($fd)){
                $_fd=$fd[0];
                $_returnFd=$fd[1];
            }
            $sql.=$func."(".$table->getAlias().".".$_fd.") AS ".$_returnFd;
        }
        if(!is_array($groupFields) && !empty($groupFields)){
            $groupFields=[$groupFields];
        }
        if(!empty($groupFields)){
            foreach ($groupFields as $fd){
                if(!empty($sql)){
                    $sql.=",";
                }
                $sql.=$table->getAlias().".".$fd;
                if(!empty($groupBy)){
                    $groupBy.=",";
                }
                $groupBy.=$fd;
            }
        }


        $sql="SELECT ".$sql." FROM ".$table->getName()." ".$table->getAlias();
        if(!empty($filter)){
            $sql.=" WHERE ".$filter;
        }
        if(!empty($groupBy)){
            $sql.=" GROUP BY ".$this->addAliasToFieldExp($groupBy, $table->getAlias());
        }
        if(!empty($sort)){
            $sql.=" ORDER BY ".$this->addAliasToFieldExp($sort, $table->getAlias());
        }
        $sql=$this->mapSqlExpression($modelClass, $sql);
        return $this->getDatabase()->query($sql,$params);
    }

    /**
     * 执行原生SQL语句
     * @param string $sql   SQL语句
     * @param array $params 输入参数
     */
    public function sqlUpdate($sql,array $params=[])
    {
        if(!$this->m_transactionStatus){
            $this->getDatabase()->begin();
        }
        $rs=$this->getDatabase()->execute($sql,$params);

        if (is_null($this->getException())) {
            if (! $this->m_transactionStatus){
                $this->getDatabase()->commit();
            }
            return $rs;
        } else {
            if (! $this->m_transactionStatus){
                $this->getDatabase()->rollback();
            }
            throw $this->getException();
        }
    }

    /**
     * 原生SQL查询
     * @param string $sql
     * @param array $params
     * @param number $offset
     * @param number $limit
     */
    public function sqlQuery($sql,array $params=[],$offset=0,$limit=-1)
    {
        return $this->getDatabase()->query($sql,$params,$offset,$limit);
    }

    /**
     /**
     * 映射翻译过滤表达式
     * @param object|string $modelClass 实体对象或类型名
     * @param string $expression        表达式
     * @param bool $autoAppendAlias     是否自动加上别名
     * @return string
     */
    public function mapSqlExpression($model,$expression,$autoAppendAlias=false)
    {
        if(empty($expression)){
            return $expression;
        }
        $table=$this->getOrmConfig()->getTable($model);
        $tbl=$table->getName();
        $alias=$table->getAlias();
        $cols=$table->getColumnNames();

        //保护参数占位符
        $pattern="/:([\w]{1,})/";
        $matches=[];
        preg_match_all($pattern, $expression,$matches);
        $paramMap=[];
        foreach ($matches[1] as $paramName){
            $key=SecurityUtil::newGuid();
            $paramMap[$key]=$paramName;
            $expression=str_replace(":".$paramName, ":".$key, $expression);
        }

        //替换有表前缀的表达式
        $map=[];
        foreach ($cols as $col){
            $field=EntityUtil::mapModelField($model, $col);
            if(!empty($field)){
                $map[$col]=$field;
                $replace=(empty($alias)?$tbl:$alias).".".$col;
                $search=$alias.".".$field;
                $expression=str_replace($search, $replace, $expression);
            }
        }

        //替换没有前缀的表达式
        $expression="--".$expression;
        foreach ($map as $dbField=>$field){
            $replace=$dbField;
            if($autoAppendAlias){
                $replace=(empty($alias)?$tbl:$alias).".".$dbField;
            }
            $pattern="/([^\w\.]{1,1})".$field."/";
            $matches=[];
            preg_match_all($pattern, $expression,$matches);
            if(count($matches[0])>0){
                for($i=0;$i<count($matches[0]);$i++){
                    $search=$matches[0][$i];
                    $_replace=$matches[1][$i].$replace;
                    $expression=str_replace($search, $_replace, $expression);
                }
            }
        }

        //恢复输入参数占位符
        foreach ($paramMap as $key=>$value){
            $expression=str_replace(":".$key, ":".$value, $expression);
        }

        $expression=substr($expression, 2);
        return $expression;
    }

    /**
     * 加载多对一字段数据
     * @param object $model 数据模型
     * @param Table $tableObj 表对象
     * @throws \Exception
     */
    private function loadManyToOneJoins($model,Table $tableObj)
    {
        $manyToOnes=$tableObj->getManyToOneJoins();
        foreach ($manyToOnes as $name=>$many2one){
            //$many2one=new ManyToOneJoin();//test
            //字段类型名
            $class=$many2one->getClass();
            if(empty($class)||!class_exists($class)){
                continue;
            }

            //目标表对象(类型与表必须设置过映射,否则无法加载)
            $targetTableObj=$this->getOrmConfig()->getTable($class);
            if(empty($targetTableObj)||empty($targetTableObj->getName())){
                continue;
            }
            $joins=$many2one->getJoins();
            if(empty($joins)){
                continue;
            }

            //sql
            $sql="SELECT {TARGET_ALIAS}.*"." FROM " . $tableObj->getName() . " " . $tableObj->getAlias();

            //join tables
            $alias=$targetTableObj->getAlias();
            foreach ($joins as $join){
                //$join=new Join();//test
                $sql.=" LEFT JOIN ".$join->getTable()." ".$join->getAlias()." ON ".$join->getOn();
                if($join->getTable()==$targetTableObj->getName()){
                    $alias=$join->getAlias();
                }
            }
            $sql=str_replace("{TARGET_ALIAS}", $alias, $sql);


            //主表的过滤
            $filter = "";
            $params=[];
            foreach ($tableObj->getPrimaryKeys() as $keyField) {
                $field=EntityUtil::mapModelField($model, $keyField);
                if ($filter != ""){
                    $filter .= " AND ";
                }
                if(empty($field)){
                    throw new \Exception("实体'".get_class($model)."'不包含主键主段'".$keyField."'");
                }
                $filter .= $tableObj->getAlias() . "." . $keyField . "=:" . $keyField;
                $params[$keyField]=EntityUtil::getFieldValue($model, $field);
            }
            $sql .= " WHERE " . $filter;

            //查询数据库
            $reader=$this->getDatabase()->reader($sql,$params);
            if ($reader && is_null($this->getDatabase()->getException())) {
                //创建一方对象
                $oneObj=new $class();
                foreach ($reader as $fieldName=> $value) {
                    $field=EntityUtil::mapModelField($oneObj, $fieldName);
                    if(!empty($field)){
                        EntityUtil::setFieldValue($oneObj, $field, $value);
                        EntityUtil::setFieldValue($model, $name, $oneObj);
                    }
                }
            }

        }
    }

    /**
     * 加载一对多集合数据
     * @param object $model 数据模型
     * @param Table $tableObj 表对象
     * @param bool $aliasToEntity 映射到实体数组(映射端口必须提供class属性)
     * @throws \Exception
     */
    private function loadOneToManyJoins($model,Table $tableObj,$aliasToEntity)
    {
        $oneToManys=$tableObj->getOneToManyJoins();
        $table=$tableObj->getName();
        $alias=$tableObj->getAlias();
        $primaryKeys = $tableObj->getPrimaryKeys();
        foreach ($oneToManys as $name => $set) {
            if(!EntityUtil::fieldExists($model, $name)){
                continue;
            }
            $joins = $set->getJoins();
            $sql = " FROM " . $table . " " . $alias;
            foreach ($joins as $join) {
                $_table = $join->getTable();
                $_alias = $join->getAlias();
                $_on = $join->getOn();
                $sql .= " JOIN " . $_table . " " . $_alias . " ON " . $_on;
            }

            $filter = "";
            $params=[];
            foreach ($primaryKeys as $keyField) {
                $field=EntityUtil::mapModelField($model, $keyField);
                if ($filter != ""){
                    $filter .= " AND ";
                }
                if(empty($field)){
                    throw new \Exception("实体'".get_class($model)."'不包含主键主段'".$keyField."'");
                }
                $filter .= $alias . "." . $keyField . "=:" . $keyField;
                $params[$keyField]=EntityUtil::getFieldValue($model, $field);
            }
            $sql .= " WHERE " . $filter;

            $fields = $set->getColumns();//需要提取的字段表达式
            if ($fields == ""){
                $fields = "*";
            }
            $sql = "SELECT " . $fields . $sql;
            $order = $set->getOrder();
            if (! empty($order)){
                $sql .= " order by " . $order;
            }
            $_sets = $this->getDatabase()->query($sql,$params);

            //class属性
            $class=$set->getClass();
            if(count($_sets)>0){
                if($aliasToEntity && !empty($class) && class_exists($class)){
                    //扩展到实体
                    $objs=[];
                    for($i=0;$i<count($_sets);$i++){
                        $row=$_sets[$i];
                        $obj=new $class();
                        foreach ($row as $col=>$val){
                            $field=EntityUtil::mapModelField($obj, $col);
                            if(!empty($field)){
                                EntityUtil::setFieldValue($obj, $field, $val);
                            }
                        }
                        $objs[]=$obj;
                    }
                    EntityUtil::setFieldValue($model, $name, $objs);

                }else {
                    //扩展到数组
                    $first=$_sets[0];
                    $keys=array_keys($first);
                    for($i=0;$i<count($_sets);$i++){
                        $row=$_sets[$i];
                        foreach ($row as $col=>$val){
                            $humpCol=StringUtil::toHumpString($col);
                            if(!in_array($humpCol, $keys)){
                                $row[$humpCol]=$val;
                            }
                        }
                        $_sets[$i]=$row;
                    }
                    EntityUtil::setFieldValue($model, $name, $_sets);
                }
            }
        }
    }

    /**
     * 编译多对一的返回列
     * @param ManyToOneJoin[] $manyToOneJoins
     * @param array $queryFieldMap              返回列信息集:键为数据库真实返回的列名,值("target":一方名称,"field":一方字段名,"query":查询输入的列名,"class":一方类名)
     * @return Join[]                           返回已编译过的join，键为表别名
     */
    private function compileManyToOneColumns($manyToOneJoins,&$queryFieldMap)
    {
        $joinMap=[];
        foreach ($manyToOneJoins as $name=>$many2one){
            $joins=$many2one->getJoins();
            if(count($joins)==0){
                continue;
            }

            //一方的表对象(一方类型必须经过映射配置)
            $oneClass=$many2one->getClass();
            $oneTable=$this->getOrmConfig()->getTable($oneClass);
            if(empty($oneClass)||empty($oneTable)){
                continue;
            }
            $oneTableName=$oneTable->getName();
            //$oneAlias=$oneTable->getAlias();//此别名为原始配置的别名,不是多对一中配置的别名,此写法不可用

            //join
            $oneAlias="";
            foreach ($joins as $join){
                $_alias=$join->getAlias();
                if(!array_key_exists($_alias, $joinMap)){
                    $joinMap[$_alias]=$join;
                }

                //根据表名匹配别名
                if($join->getTable()==$oneTableName){
                    $oneAlias=$join->getAlias();
                }
            }

            //只需要返回一方的列,其它的抛弃
            $cols=$oneTable->getColumnNames();
            foreach ($cols as $col){
                $colName="_".$name."_".$col;//数据库真实输出的列名(查询列别名)
                $query=$oneAlias.".".$col." AS ".$colName;//向数据库查询输入的列名
                $queryFieldMap[$colName]=["target"=>$name,"field"=>$col,"query"=>$query,"class"=>$oneClass];
            }
        }
        return $joinMap;
    }

    /**
     * 编译多对一的过滤表达式
     * @param ManyToOneJoin[] $manyToOneJoins   ManyToOneJoin集
     * @param string $exp                       待编译的过滤或排序表达式
     * @return Join[]                           返回已编译过的join，键为表别名
     */
    private function compileManyToOneExp($manyToOneJoins,&$exp)
    {
        $_joins=[];
        if(empty($exp)){
            return [];
        }
        foreach ($manyToOneJoins as $name=>$one){
            //写法:点号前后不能有空格
            if(strpos($exp, $name.".")!==false){
                $joins=$one->getJoins();
                if(count($joins)==0){
                    continue;
                }
                foreach ($joins as $join){
                    $_alias=$join->getAlias();
                    if(!in_array($_alias, $_joins)){
                        $_joins[$_alias]=$join;
                    }
                }

                //映射字段(一方类型必须经过映射配置)
                $class=$one->getClass();
                $_tableObj=$this->getOrmConfig()->getTable($class);
                foreach ($_tableObj->getColumnNames() as $dbField){
                    $field=EntityUtil::mapModelField($class, $dbField);
                    $space=" ";
                    if(strpos($exp, $name.".")===0){
                        $space="";
                    }
                    $exp=str_replace($space.$name.".".$dbField, $space.$_alias.".".$dbField, $exp);
                    $exp=str_replace($space.$name.".".$field, $space.$_alias.".".$dbField, $exp);
                }
            }
        }
        return $_joins;
    }

    /**
     * 添加表前缀名到字段表达式
     * @param string $exp
     * @param string $alias
     * @return string
     */
    private function addAliasToFieldExp($exp,$alias)
    {
        if(trim($exp)==""){
            return "";
        }
        $_exp="";
        $fds = explode(",", $exp);
        foreach ($fds as $fd) {
            if (strpos($fd, ".") === false){
                $fd = $alias . "." . $fd;
            }
            if ($_exp != ""){
                $_exp .= ",";
            }
            $_exp .= $fd;
        }
        return $_exp;
    }
}