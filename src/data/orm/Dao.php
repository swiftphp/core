<?php
namespace swiftphp\core\data\orm;

use swiftphp\core\config\IConfigurable;
use swiftphp\core\data\orm\mapping\Config;
use swiftphp\core\data\db\IDatabase;
use swiftphp\core\system\ILogger;
use swiftphp\core\config\IConfiguration;
use swiftphp\core\utils\StringUtil;
use swiftphp\core\utils\Convert;
use swiftphp\core\data\types\Type;
use swiftphp\core\data\orm\mapping\Table;
use swiftphp\core\data\orm\mapping\Column;

/**
 * 数据DAO
 * @author Tomix
 *
 */
class Dao implements IConfigurable
{
    /**
     * 是否调试状态
     * @var bool
     */
    private $m_debug=false;

    /**
     * 数据源
     * @var IDatabase
     */
    private $m_database;

    /**
     * 日志
     * @var ILogger
     */
    private $m_logger=null;

    /**
     * 当前ORM配置
     * @var Config
     */
    private $m_ormConfig=null;

    /**
     * 配置实例
     * @var IConfiguration
     */
    private $m_config=null;

    /**
     * 事务状态：true表示状态已挂起
     * @var bool
     */
    private $m_transactionStatus=false;

    /**
     * 是否调试状态
     * @param bool $value
     */
    public function setDebug($value)
    {
        $this->m_debug=$value;
    }

    /**
     * 注入数据源描述
     * @param IDatabase $value
     */
    public function setDatabase($value)
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
     * 注入日志记录器
     * @param ILogger $value
     */
    public function setLogger(ILogger $value)
    {
        $this->m_logger=$value;
    }

    /**
     * ORM配置
     * @param Config $value
     */
    public function setOrmConfig(Config $value)
    {
        $this->m_ormConfig=$value;
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
     * 获取当前ORMs配置
     * @return Config
     */
    public function getOrmConfig()
    {
        return $this->m_ormConfig;
    }

    /**
     * 加载ORM配置
     */
    public function config()
    {
        //连接数据源
        $this->m_database->connect();

        //加载ORM配置
        $this->m_ormConfig->setDatabase($this->m_database);
        $this->m_ormConfig->load();
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
        $cols = $this->getOrmConfig()->getTable($model)->getColumns();
        foreach ($cols as $name => $col) {
            $field=$this->mapModelField($model, $name);
            if (!empty($field)) {
                $model->$field= $col->getDefault();
            }
        }
    }

    /**
     * 加载数据
     * @param object $model 实体对象
     * @param string|array $fields 查询字段,用实体属性名表示,留空表示按主键主段查询
     * @param string $sync
     * @throws \Exception
     * @return boolean
     */
    public function load($model, $fields = null, $sync = true)
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
        if (is_array($fields) && count($fields) > 0) {
            //按指定字段查询
            foreach ($fields as $field) {
                if(!property_exists($model,$field)){
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
                $filter .= $dbField. "='" . $model->$field . "'";
            }
        } else {
            //按主键查询
            foreach ($primaryKeys as $keyField) {
                $field=$this->mapModelField($model, $keyField);
                if(empty($field)){
                    throw new \Exception("实体'".get_class($model)."'不存在主键标识'".$keyField."'");
                }
                if ($filter != ""){
                    $filter .= " AND ";
                }
                $filter .= $keyField . "='" . $model->$field. "'";
            }
        }
        $sql = "SELECT * FROM " . $table . "";
        $sql .= " WHERE " . $filter;

        $reader=$this->getDatabase()->reader($sql);
        if ($reader && is_null($this->getDatabase()->getException())) {
            foreach ($reader as $fieldName=> $value) {
                $field=$this->mapModelField($model, $fieldName);
                if(!empty($field)){
                    $model->$field= $value;
                }
            }
        } else {
            return false;
        }

        //加载集合属性(关联表)与多对一属性,映射到二维数组,键为字段名或映射驼峰命名字段名
        if ($sync) {
            //加载一对多
            $this->loadOneToManyJoins($model, $tableObj);
            //加载多对一
            $this->loadManyToOneJoins($model, $tableObj);

        }
        if (is_null($this->getException())) {
            return true;
        } else {
            throw $this->getException();
        }
    }

    /**
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
        foreach ($columns as $columnName) {
            $fieldName=$this->mapModelField($model, $columnName);
            if (! in_array($columnName, $incrementKeys) && !empty($fieldName)) {
                $column = $tableObj->getColumn($columnName);
                if ($fields != ""){
                    $fields .= ",";
                }
                $fields .= $columnName;

                // 字段值
                $fieldValue = $model->$fieldName;
                if ($values != ""){
                    $values .= ",";
                }
                if ($fieldValue === null || ($fieldValue === "" && $column->getType() != Type::STRING)) {
                    $values .= "NULL";
                } else {
                    $values .= "'" . Convert::toDbString($fieldValue) . "'";
                }
            }
        }
        $sql = "INSERT INTO " . $table . " ({0}) VALUES ({1})";
        $sql = str_replace("{0}", $fields, $sql);
        $sql = str_replace("{1}", $values, $sql);

        //执行操作
        $this->getDatabase()->execute($sql);
        $insertId = $this->getDatabase()->getInsertId();

        //提取插入ID重新赋值到模型属性
        if (! empty($insertId)) {
            foreach ($primaryKeys as $pKey) {
                $propName=$this->mapModelField($model, $pKey);
                if (in_array($pKey, $incrementKeys) && !empty($propName)) {
                    $model->$propName= $insertId;
                    break;
                }
            }
        }

        //插入关联数据
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
                    $keyField=$this->mapModelField($model, $_key);//映射到实体属性字段
                    if(empty($keyField)){
                        continue;
                    }
                    // foreach rows
                    foreach ($model->$name as $row) {
                        $fields = "";
                        $values = "";
                        foreach ($_tableObj->getColumns() as $col) {
                            $dbField = $col->getName();//表字段名
                            $field=$this->mapArrayKey($row, $dbField);//数组键
                            $value = null;
                            if ($dbField== $_fkey) {
                                $value = $model->$keyField;
                            } elseif (!empty($field)) {
                                $value = $row[$field];
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
                                if ($value=== null || ($value=== "" && $col->getType() != Type::STRING)) {
                                    $values .= "NULL";
                                } else if($col->getType()==Type::INTEGER || $col->getType()==Type::DOUBLE){
                                    $values .= $value;
                                }else {
                                    $values .= "'" . Convert::toDbString($value) . "'";
                                }
                            }
                        }
                        $sql = "INSERT INTO " . $_table . " ({0}) VALUES ({1})";
                        $sql = str_replace("{0}", $fields, $sql);
                        $sql = str_replace("{1}", $values, $sql);
                        $this->getDatabase()->execute($sql);
//                         echo $sql."\r\n";
//                         $this->getDatabase()->rollbackTransaction();
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
     * 更新一条记录,返回bool类型
     *
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
        foreach ($properties as $fieldName) {
            $field=$this->mapModelField($model, $fieldName);
            if(empty($field)){
                if(in_array($fieldName, $primaryKeys)){
                    throw new \Exception("实体'".get_class($model)."'不包含主键字段.".$fieldName."'");
                }else{
                    continue;
                }
            }
            $column = $tableObj->getColumn($fieldName);
            if (in_array($fieldName, $primaryKeys)) {
                // 主键字段
                if ($filter != ""){
                    $filter .= " AND ";
                }
                $filter .= $alias . "." . $fieldName . "='" . Convert::toDbString($model->$field). "'";
            } elseif (! in_array($fieldName, $incrementKeys)) {
                // 非主键字段
                if ($update != ""){
                    $update .= ",";
                }

                // 字段值
                $fieldValue = $model->$field;
                if ($fieldValue === null || ($fieldValue === "" && $column->getType() != Type::STRING)) {
                    $update .= $alias . "." . $fieldName . "=NULL";
                } else {
                    $update .= $alias . "." . $fieldName . "='" . Convert::toDbString($fieldValue) . "'";
                }
            }
        }
        $sql = "UPDATE " . $table . " " . $alias . " SET {0} WHERE {1}";

        //添加版本控制实现乐观锁
        $versionFilter=$filter;
        $versionField=$tableObj->getVersion();
        $modelVerField=$this->mapModelField($model, $versionField);
        if(!empty($forVersion) && !empty($versionField) && !empty($modelVerField)){
            $versionFilter.=" AND (".$versionField."='".$model->$modelVerField."' OR ".$versionField." IS NULL)";
            if ($update != ""){
                $update .= ",";
            }
            $update.=$versionField."='".$forVersion."'";
            $sql = str_replace("{1}", $versionFilter, $sql);
        }
        //添加版本控制代码完毕

        //执行操作
        $sql = str_replace("{0}", $update, $sql);
        $sql = str_replace("{1}", $filter, $sql);
        $_rows=$this->getDatabase()->execute($sql);
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
                    $keyField=$this->mapModelField($model, $_key);//映射到实体属性字段
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
                    $rs = $this->getDatabase()->query($sql);

                    // index rows by keys
                    $_rs = [];
                    foreach ($rs as $row) {
                        $key_str = "";
                        foreach ($_tableObj->getPrimaryKeys() as $pk) {
                            if ($key_str != ""){
                                $key_str .= ",";
                            }
                            $key_str .= $row[$pk];
                        }
                        $_rs[$key_str] = $row;
                    }

                    // scan this prop for set
                    foreach ($model->$name as $row) {
                        // $_key=null;//主表提供的键
                        // $_fkey=null;//从表提供的外键

                        // //从表的外键从主表赋值,程序更新时不需要设置外键值
                        $row[$_fkey] = $model->$keyField;

                        // key string
                        $key_str = "";
                        foreach ($_tableObj->getPrimaryKeys() as $pk) {
                            if ($key_str != ""){
                                $key_str .= ",";
                            }
                            if(!array_key_exists($pk, $row)){
                                $pk=$this->mapArrayKey($row, $pk);
                            }
                            if(!array_key_exists($pk, $row)){
                                throw new \Exception("实体'".get_class($model)."'的属性'".$name."'未提供主键'".$pk."'");
                            }
                            $key_str .= $row[$pk];
                        }

                        // if row exists
                        if (array_key_exists($key_str, $_rs)) {
                            $_row = $_rs[$key_str];//旧记录
                            if (count($_tableObj->getColumns()) > count($_tableObj->getPrimaryKeys())) {
                                // update
                                $_filter = "";
                                $update = "";
                                foreach ($_tableObj->getColumns() as $col) {
                                    $field = $col->getName();
                                    $indexName=$field;

                                    //驼峰属性优先取值
                                    if($humpFieldFirst){
                                        $indexName=StringUtil::toHumpString($field);
                                    }

                                    //如果驼峰属性不存在,则重新映射
                                    if(!array_key_exists($indexName, $row)){
                                        $indexName=$this->mapArrayKey($row, $field);
                                    }

                                    //属性不存在,则放弃该字段的更新
                                    if(empty($indexName) || !array_key_exists($indexName, $row)){
                                        continue;
                                    }
                                    if (in_array($field, $_tableObj->getPrimaryKeys())) {

                                        // 主键字段
                                        if ($_filter != ""){
                                            $_filter .= " AND ";
                                        }
                                        if($col->getType()==Type::INTEGER || $col->getType()==Type::DOUBLE){
                                            $_filter .= $_alias . "." . $field . "=" . $_row[$field];
                                        }else{
                                            $_filter .= $_alias . "." . $field . "='" . $_row[$field] . "'";
                                        }
                                    } else if (! in_array($field, $_tableObj->getIncrementKeys())) {
                                        // 非主键,非自动递增字段
                                        if ($update != "")
                                            $update .= ",";
                                            $_fieldValue = $row[$indexName];
                                            if ($_fieldValue === null || ($_fieldValue === "" && $col->getType() != Type::STRING)) {
                                                $update .= $_alias . "." . $field . "=NULL";
                                            } else if($col->getType()==Type::INTEGER || $col->getType()==Type::DOUBLE){
                                                $update .= $_alias . "." . $field . "=" . $_fieldValue;
                                            }else {
                                                $update .= $_alias . "." . $field . "='" . Convert::toDbString($_fieldValue) . "'";
                                            }
                                            //$update .= $_alias . "." . $field . "='" . convert::toDbString($row[$field]) . "'";
                                    }
                                }
                                $sql = "UPDATE " . $_table . " " . $_alias . " SET {0} WHERE {1};";
                                $sql = str_replace("{0}", $update, $sql);
                                $sql = str_replace("{1}", $_filter, $sql);
                                if(!empty($update) && !empty($_filter)){
                                    $this->getDatabase()->execute($sql);
                                }
                            }

                            // remove this row
                            unset($_rs[$key_str]);
                        }else {
                            //if row not exists,then insert
                            $fields = "";
                            $values = "";
                            foreach ($_tableObj->getColumns() as $col) {
                                //字段名与映射字段名
                                $field = $col->getName();
                                $indexName=$field;
                                if($humpFieldFirst){
                                    $indexName=StringUtil::toHumpString($field);
                                }
                                if(!array_key_exists($indexName, $row)){
                                    $indexName=$this->mapArrayKey($row, $field);
                                }
                                if(empty($indexName) || !array_key_exists($indexName, $row)){
                                    continue;
                                }

                                //字段值
                                $value = null;
                                if ($field == $_fkey) {
                                    $value = $model->$keyField;//从表的外键从实体主键取值
                                } else{
                                    $value = $row[$indexName];
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
                                    if ($value === null || ($value === "" && $col->getType() != Type::STRING)) {
                                        $values .= "NULL";
                                    } else if($col->getType()==Type::INTEGER || $col->getType()==Type::DOUBLE){
                                        $values.=$value;
                                    }else {
                                        $values .= "'" . Convert::toDbString($value) . "'";
                                    }
                                }
                            }
                            $sql = "INSERT INTO " . $_table . " ({0}) VALUES ({1});";
                            $sql = str_replace("{0}", $fields, $sql);
                            $sql = str_replace("{1}", $values, $sql);
                            $this->getDatabase()->execute($sql);
                        }
                    }

                    // delete not existing row
                    foreach ($_rs as $row) {
                        $_filter = "";
                        foreach ($_tableObj->getPrimaryKeys() as $pk) {
                            if ($_filter != ""){
                                $_filter .= " AND ";
                            }
                            $_filter .= $pk . "='" . $row[$pk] . "'";
                        }
                        $sql = "DELETE FROM " . $_table . " WHERE " . $_filter . ";";
                        $this->getDatabase()->execute($sql);
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
     * @param unknown $model
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
        foreach ($primaryKeys as $keyField) {
            $field=$this->mapModelField($model, $keyField);
            if(empty($field)){
                throw new \Exception("实体'".get_class($model)."'不包含主键字段'".$keyField."'");
            }
            if ($filter != ""){
                $filter .= " AND ";
            }
            $filter .= $alias . "." . $keyField . "='" . $model->$field. "'";
        }

        // sql语句组
        $sql_array = [];

        // 删除关联表记录(sets标记不能同步删除)

        // 删除关联表记录(dels标记)
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
        foreach ($sql_array as $sql) {
            $intRows = $this->getDatabase()->execute($sql);
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
    public function deletes($modelClass, $filter = "")
    {
        $table = $this->getOrmConfig()->getTable($modelClass)->getName();
        $sql = "DELETE FROM " . $table;
        if (!empty($filter)){
            $filter=$this->mapSqlExpression($modelClass, $filter);
            $sql .= " WHERE " . $filter;
        }
        return $this->getDatabase()->execute($sql);
    }

    /**
     *
     * @param mixed $modelClass
     * @param string $filter 主表或关联表过滤字段(看参数$joinFilter参数是否为true)
     * @param string $sort 主表排序字段
     * @param number $offset
     * @param number $length
     * @param string $fields
     * @param bool $toHumpFields
     * @param string $groupBy
     * @param bool $joinFilter 是否关联过滤
     */
    public function select($modelClass, $filter = "", $sort = "", $offset = 0, $length = -1,$withManyToOne=true, $fields = "",$toHumpFields=true, $groupBy = "", $joinFilter = false)
    {
        // mapping config
        $tableObj = $this->getOrmConfig()->getTable($modelClass);
        $table = $tableObj->getName();
        $alias = $tableObj->getAlias();
        if(empty($alias)){
            $alias="_".$table;
        }
        $select = $tableObj->getSelectJoin();
        $manyToOneJoins=$tableObj->getManyToOneJoins();

        //sql
        $sql = "SELECT {COLUMNS} FROM " . $table;
        if (!empty($filter)){
            $sql .= " WHERE " . $filter;
        }

        //多对一字段映射
        $manyToOneFieldMap=[];

        //没有配置映射,通用写法
        if (empty($select)) {
            if(empty($manyToOneJoins)){
                //没有多对一的查询
                if ($fields == ""){
                    $fields = "*";
                }
                $sql = str_replace("{COLUMNS}", $fields, $sql);
                //$sql="SELECT ".$alias.".".$
                if (trim($groupBy) != ""){
                    $sql .= " GROUP BY " . $groupBy;
                }
                if (trim($sort) != ""){
                    $sql .= " ORDER BY " . $sort;
                }
            }else if($withManyToOne){
                //具有多对一的查询
                $sql = str_replace("{COLUMNS}", "*", $sql);
                $sql = "SELECT {COLUMNS} FROM (" . $sql . ") " . $alias;
                if ($fields == ""){
                    $fields = "*";
                }
                $fields=$this->addAliasToFieldExp($fields, $alias);

                $oneFields="";  //一方的列
                $joinSql="";    //join语句
                $this->selectManyToOneJoins($manyToOneJoins, $oneFields, $joinSql,$manyToOneFieldMap);
                if(!empty($oneFields) && !empty($joinSql)){
                    $fields.=",".$oneFields;
                    $sql.=$joinSql;
                }
                $sql = str_replace("{COLUMNS}", $fields, $sql);
//                 echo $sql;
//                 exit;
            }
        } else {
            //配置映射
            if ($joinFilter) {
                $sql = "SELECT {COLUMNS} FROM " . $table . " " . $alias;
            } else {
                $sql = str_replace("{COLUMNS}", "*", $sql);
                $sql = "SELECT {COLUMNS} FROM (" . $sql . ") " . $alias;
            }

            $_fields = $alias . ".*";
            $cols = $select->getColumns();
            if (! empty($cols)){
                $_fields = $cols;
            }
            if (! empty($fields)){
                $_fields = $fields;
            }

            //关联过滤
            foreach ($select->getJoins() as $join) {
                $_table = $join->getTable();
                $_alias = $join->getAlias();
                $_on = $join->getOn();
                $sql .= " LEFT JOIN " . $_table . " " . $_alias . " ON " . $_on;
            }

            // fields
            $fields = $this->addAliasToFieldExp($_fields, $alias);

            //启用多对一查询
            if($withManyToOne){
                $oneFields="";  //一方的列
                $joinSql="";    //join语句
                $this->selectManyToOneJoins($manyToOneJoins, $oneFields, $joinSql,$manyToOneFieldMap);
                if(!empty($oneFields) && !empty($joinSql)){
                    $fields.=",".$oneFields;
                    $sql.=$joinSql;
                }
            }

            // group
            $_group = $this->addAliasToFieldExp($groupBy, $alias);
            if (trim($_group) != "")
                $sql .= " GROUP BY " . $_group;

            // join filter
            if ($joinFilter && ! empty($filter)) {
                $sql .= " WHERE " . $filter;
            }

            // order
            $_order = $this->addAliasToFieldExp($sort, $alias);
            if (trim($_order) != ""){
                $sql .= " ORDER BY " . $_order;
            }
            $sql = str_replace("{COLUMNS}", $fields, $sql);
// echo $sql."\r\n";
// exit;
        }
        $sql=$this->mapSqlExpression($modelClass, $sql);
//                     echo $sql."\r\n";
//                     exit;

        $rs = $this->getDatabase()->query($sql, $offset, $length);


        //拆分多对一的字段为数组
        if(count($rs)>0){
            $keys=array_keys($rs[0]);
            for($i=0;$i<count($rs);$i++){
                $line=$rs[$i];
                $many2one=[];
                foreach ($line as $key=>$value){
                    if(array_key_exists($key, $manyToOneFieldMap)){
                        //many2one的字段
                        $map=$manyToOneFieldMap[$key];
                        $name=$map["name"];
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
                $rs[$i]=$line;
            }
        }

        //转换为驼峰命名的列
        if($toHumpFields && count($rs)>0){
            $keys=array_keys($rs[0]);
            for($i=0;$i<count($rs);$i++){
                $dr=$rs[$i];
                foreach ($dr as $key=>$value){
                    if(!is_numeric($key)){
                        $key=StringUtil::toHumpString($key);
                        if(!array_key_exists($key, $keys)){
                            $dr[$key]=$value;
                        }
                    }
                }
                $rs[$i]=$dr;
            }
        }

        return $rs;
    }

    /**
     * 查询计数
     * @param unknown $modelClass
     * @param string $filter
     * @param string $joinFilter
     * @return array
     */
    public function count($modelClass, $filter = "", $joinFilter = false)
    {
        // mapping config
        $tableObj = $this->getOrmConfig()->getTable($modelClass);
        $table = $tableObj->getName();
        $alias = $tableObj->getAlias();
        $select = $tableObj->getSelectJoin();

        $filter=$this->mapSqlExpression($modelClass,$filter,$joinFilter);

        if ($joinFilter && ! empty($select)) {
            $sql = "SELECT COUNT(*) FROM " . $table . " " . $alias;
            foreach ($select->getJoins() as $join) {
                $_table = $join->getTable();
                $_alias = $join->getAlias();
                $_on = $join->getOn();
                $sql .= " LEFT JOIN " . $_table . " " . $_alias." ON ".$_on;
            }
            $sql .= " WHERE ".$filter;
        }else{
            $sql="SELECT COUNT(*) FROM ".$table."";
            if(trim($filter) != "")
                $sql .= " WHERE ".$filter;
        }
        //         echo $sql;
        //         exit;
        return $this->getDatabase()->scalar($sql);
    }

    /**
     * 数据聚合统计(函数名必须与对应的数据库一致)
     * @param string|object $modelClass  模型类名或实例
     * @param string $filter             过滤表达式
     * @param string $sort               排序表达式
     * @param array $funcMap             统计函数,键为函数名,值为统计字段;至少包含一个元素
     * @param array $groupFields         分组统计字段,可选
     */
    public function group($modelClass,$filter = "",$sort="",$funcMap=[],$groupFields=[])
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
            $sql.=$func."(".$table->getAlias().".".$fd.") AS ".$fd;
        }
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


        $sql="SELECT ".$sql." FROM ".$table->getName()." ".$table->getAlias();
        if(!empty($filter)){
            $sql.=" WHERE ".$filter;
        }
        if(!empty($groupBy)){
            $sql.=" GROUP BY ".$groupBy;
        }
        if(!empty($sort)){
            $sql.=" ORDER BY ".$sort;
        }
        $sql=$this->mapSqlExpression($modelClass, $sql);
        return $this->getDatabase()->query($sql);
    }

    /**
     * 原生SQL执行
     * @param unknown $sql
     */
    public function sqlUpdate($sql)
    {
        if(!$this->m_transactionStatus){
            $this->getDatabase()->begin();
        }
        $rs=$this->getDatabase()->execute($sql);

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
     * @param string $sql 原生SQL
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function sqlQuery($sql,$offset=0,$limit=-1)
    {
        return $this->getDatabase()->query($sql,$offset,$limit);
    }

    /**
     * 数据字段名映射到模型属性名
     * @param mixed $model 模型实例或模型名称
     * @param string $dbField 数据库字段名
     */
    public function mapModelField($model,$dbField)
    {
        //与表字段名一致
        if(property_exists($model, $dbField)){
            return $dbField;
        }

        //转为小写起的驼峰名
        $fd=StringUtil::toHumpString($dbField);
        if(property_exists($model, $fd)){
            return $fd;
        }

        //转为大写起的驼峰名
        $fd=ucfirst($fd);
        if(property_exists($model, $fd)){
            return $fd;
        }
        return false;
    }

    /**
     * 数据字段名映射到数组键名
     * @param array $array
     * @param string $dbField
     */
    public function mapArrayKey(array $array,$dbField)
    {
        //与表字段名一致
        if(array_key_exists($dbField, $array)){
            return $dbField;
        }

        //转为小写起的驼峰名
        $fd=StringUtil::toHumpString($dbField);
        if(array_key_exists($fd, $array)){
            return $fd;
        }

        //转为大写起的驼峰名
        $fd=ucfirst($fd);
        if(array_key_exists($fd, $array)){
            return $fd;
        }
        return false;

    }

    /**
     * 映射翻译过滤表达式
     * @param Table $table
     * @param string $filter
     * @return string
     */
    public function mapSqlExpression($model,$expression,$mapJoins=false)
    {
        if(empty($expression)){
            return $expression;
        }
        $table=$this->getOrmConfig()->getTable($model);
        $tbl=$table->getName();
        $alias=$table->getAlias();
        $cols=$table->getColumnNames();

        //替换有表前缀的表达式
        $map=[];
        foreach ($cols as $col){
            $field=$this->mapModelField($model, $col);
            if(!empty($field)){
                $map[$col]=$field;
                $replace=(empty($alias)?$tbl:$alias).".".$col;
                $search=$tbl.".".$field;
                $expression=str_replace($search, $replace, $expression);
                $search=$alias.".".$field;
                $expression=str_replace($search, $replace, $expression);
            }
        }

        //替换关联表过滤.
        if($mapJoins){
            $selectJoin=$table->getSelectJoin();
            if(!empty($selectJoin)){
                //<join table="crm_customer" alias="c" on="c.id=o.customerId" />
                $joins=$selectJoin->getJoins();
                foreach ($joins as $join){
                    //$_on=$join->getOn();
                    $_tbl=$join->getTable();
                    $_alias=$join->getAlias();
                    $_table=new Table();
                    $_table->setName($_tbl);
                    $_table->setAlias($_alias);
                    $this->getOrmConfig()->mappingColumns($_table);
                    $cols=$_table->getColumnNames();
                    foreach ($cols as $col){
                        $replace=(empty($_alias)?$_tbl:$_alias).".".$col;
                        $humpCol=StringUtil::toHumpString($col);

                        $search=$_tbl.".".$humpCol;
                        $expression=str_replace($search, $replace, $expression);
                        $search=$_alias.".".$humpCol;
                        $expression=str_replace($search, $replace, $expression);

                        $search=$_tbl.".".ucfirst($humpCol);
                        $expression=str_replace($search, $replace, $expression);
                        $search=$_alias.".".ucfirst($humpCol);
                        $expression=str_replace($search, $replace, $expression);
                    }
                }
            }
        }

        //替换没有前缀的表达式
        foreach ($map as $dbField=>$field){
            $expression=str_replace($field, $dbField, $expression);
        }

        return $expression;
    }

    /**
     * 加载一对多集合数据
     * @param object $model 数据模型
     * @param Table $tableObj 表对象
     * @throws \Exception
     */
    private function loadOneToManyJoins($model,Table $tableObj)
    {
        $oneToManys=$tableObj->getOneToManyJoins();
        $table=$tableObj->getName();
        $alias=$tableObj->getAlias();
        $primaryKeys = $tableObj->getPrimaryKeys();
        foreach ($oneToManys as $name => $set) {
            if(property_exists($model, $name)){
                $joins = $set->getJoins();
                $sql = " FROM " . $table . " " . $alias;
                foreach ($joins as $join) {
                    $_table = $join->getTable();
                    $_alias = $join->getAlias();
                    $_on = $join->getOn();
                    $sql .= " JOIN " . $_table . " " . $_alias . " ON " . $_on;
                }

                $filter = "";
                foreach ($primaryKeys as $keyField) {
                    $field=$this->mapModelField($model, $keyField);
                    if ($filter != ""){
                        $filter .= " AND ";
                    }
                    if(empty($field)){
                        throw new \Exception("实体'".get_class($model)."'不包含主键主段'".$keyField."'");
                    }
                    $filter .= $alias . "." . $keyField . "='" . $model->$field. "'";
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
                // echo $sql."\r\n";
                // exit;
                $_sets = $this->getDatabase()->query($sql);
                if(count($_sets)>0){
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
                }
                $model->$name = $_sets;
            }
        }
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
        $table=$tableObj->getName();
        $alias=$tableObj->getAlias();
        $alias=empty($alias)?"_".$table:$alias;

        foreach ($manyToOnes as $name => $join) {
            if(property_exists($model, $name)){
                //$join=new ManyToOneJoin();//test
                $class=$join->getClass();
                if(!class_exists($class)){
                    continue;
                }
                $_table=$join->getTable();
                if(empty($_table)){
                    $_table=$this->getOrmConfig()->getTable($class)->getName();
                }
                $_alias=$join->getAlias();
                if(empty($_alias)){
                    $_alias="_".$_table;
                }

                //从on从拆分主表的关联字段(或外键),附表的搜索字段
                $manyKey="";   //多方(主表)中的关联键(外键)
                $onekey="";   //一方(父表)的搜索字段(主键)
                $this->matchKeys($join->getOn(), $table, $alias, $_table, $_alias, $manyKey, $onekey);
                $manyKey = $this->mapModelField($model, $manyKey);
                if(empty($manyKey)||empty($onekey)||!property_exists($model, $manyKey)){
                    continue;
                }

                //搜索条件
                $where=" WHERE ".$_alias.".".$onekey."='".$model->$manyKey."'";

                //查询数据库
                $sql="SELECT ".$_alias.".* FROM ".$_table." ".$_alias.$where;
                $reader=$this->getDatabase()->reader($sql);

                //创建一方对象
                $model->$name=new $class();
                if ($reader && is_null($this->getDatabase()->getException())) {
                    foreach ($reader as $fieldName=> $value) {
                        $field=$this->mapModelField($model->$name, $fieldName);
                        if(!empty($field)){
                            $model->$name->$field= $value;
                        }
                    }
                }
            }
        }
    }

    /**
     * 多对一查询语句
     * @param array $manyToOneJoins
     * @param string $columns
     * @param string $joinSql
     * @param boolean $toHumpFields
     */
    private function selectManyToOneJoins($manyToOneJoins,&$columns,&$joinSql,&$manyToOneFieldMap)
    {
        foreach ($manyToOneJoins as $name=>$join){
            $_table=$join->getTable();
            $_alias=$join->getAlias();
            if(empty($_table) && !empty($join->getClass())){
                $_table=$this->getOrmConfig()->getTable($join->getClass())->getName();
                //$_alias="_".$_table;
            }
            if(empty($_alias)){
                $_alias="_".$_table;
            }
            if(empty($_table)){
                continue;
            }
            $joinSql.=" LEFT JOIN ".$_table." ".$_alias." ON ".$join->getOn();

            //cols
            $fds=[];
            $cols=$join->getColumns();
            if(empty($cols)||$cols=="*"||$cols==$_alias.".*"){
                //重新映射字段
                $_tableObj=new Table();
                $_tableObj->setName($_table);
                $this->getOrmConfig()->mappingColumns($_tableObj);
                foreach ($_tableObj->getColumns() as $col){
                    $colName="_".$name."_".$col->getName();
                    $fds[]=$_alias.".".$col->getName()." AS ".$colName;
                    $manyToOneFieldMap[$colName]=["name"=>$name,"field"=>$col->getName()];
                }
                $fds=implode(",", $fds);
            }else{
                $cols=explode(",", $cols);
                foreach ($cols as $c){
                    $fd=$c;
                    if(strpos($c, ".")){
                        $fd=substr($c, strpos($c, ".")+1);
                    }
                    $colName="_".$name."_".$fd;
                    $fds[]=$_alias.".".$fd." AS ".$colName;
                    $manyToOneFieldMap[$colName]=["name"=>$name,"field"=>$fd];
                }
                $fds=implode(",", $fds);
            }
            if(!empty($columns)){
                $columns.=",";
            }
            $columns.=$fds;
        }
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


    /**
     * 从关联条件分解键名
     * @param string $on
     * @param string $table1
     * @param string $alias1
     * @param string $table2
     * @param string $alias2
     * @param string $key1
     * @param string $key2
     */
    private function matchKeys($on,$table1,$alias1,$table2,$alias2,&$key1,&$key2)
    {
        //// 从关联条件分解外键与主表字段
        $key1 = "";
        $key2 = "";
        $key_arr = explode("=", $on);
        foreach ($key_arr as $key_str) {
            $_key_arr = explode(".", $key_str);
            $_tbl = $_key_arr[0];
            if ($_tbl == $table1 || $_tbl == $alias1){
                $key1 = $_key_arr[1];
            }
            if ($_tbl == $table2 || $_tbl == $alias2){
                $key2 = $_key_arr[1];
            }
        }
    }
}