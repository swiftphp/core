<?php
namespace swiftphp\core\data\orm;

use swiftphp\core\data\orm\mapping\IConfig;

/**
 * DAO接口
 * @author Tomix
 *
 */
interface IDao
{
    /**
     * 获取最后一次产生的异常
     * @return \Exception|null
     */
    function getException();

    /**
     * 获取当前ORMs配置
     * @return IConfig
     */
    function getOrmConfig();

    /**
     * 获取当前的事务状态
     * @return bool
     */
    function getTransactionStatus();

    /**
     * 开始事务
     */
    function beginTransaction();

    /**
     * 提交事务
     */
    function commitTransaction();

    /**
     *回滚事务
     */
    function rollbackTransaction();

    /**
     * 载入实体默认值
     * @param object $model
     */
    function loadDefaultValue($model);

    /**
     * 加载数据
     * @param object $model 实体对象
     * @param string|array $fields 查询字段,用实体属性名表示,留空表示按主键主段查询
     * @param string $sync 是否同步加载关联数据(一对多与多对一,映射文件必须设置sync="true"标记)
     * @param boolean $aliasOneToManyToEntity 是否把一对多映射到实体数组(默认false为映射到键值对二维数组,映射到实体数组时,映射文件必须指定class属性)
     * @throws \Exception
     * @return boolean
     */
    function load($model, $fields = null, $sync = true,$aliasOneToManyToEntity=false);

    /**
     * 插入一条数据,成功则返回记录ID(递增)或true,失败返回false
     * @param object $model 实体对象
     * @param string $sync  是否同步插入一对多关联数据
     */
    function insert($model, $sync = true);


    /**
     * 更新一条记录,成功则返回true,失败返回false
     * @param object $model 数据实体
     * @param string $sync  是否同步到一对多关联表
     * @param bool $humpFieldFirst 更新关联表时，关联数据以二维数组表示时是否以驼峰命名的字段优先取值;对象数组表示时,此设置无效
     * @param string $forVersion 新版本号:表设置了版本号字段且新版本非空时,实现版本号校验(乐观锁)
     * @return boolean
     */
    function update($model, $sync = true,$humpFieldFirst=true, $forVersion="");

    /**
     * 删除数据
     * @param object $model
     */
    function delete($model);

    /**
     * 批量删除数据
     * @param string $modelClass 实体类型名
     * @param string $filter 过滤表达式
     * @param array $params  输入参数
     */
    function deletes($modelClass, $filter = "",array $params=[]);

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
    function select($modelClass, $filter = "",array $params=[], $sort = "", $offset = 0, $length = -1,$aliasToEntityMap=false,$withManyToOne=true,$toHumpFields=true, $fields = "");

    /**
     * 查询计数
     * @param string $modelClass 实体类型名
     * @param string $filter 过滤表达式
     * @param array $params  输入参数
     */
    function count($modelClass, $filter = "",array $params=[]);

    /**
     * 数据聚合统计(函数名必须与对应的数据库一致)
     * @param string|object $modelClass  模型类名或实例
     * @param string $filter             过滤表达式
     * @param array $params             输入参数
     * @param string $sort               排序表达式
     * @param array $funcMap             统计函数,键为函数名,值为统计字段(值为数组时,第一个元素为统计字段,第二个为返回字段);至少包含一个元素
     * @param array $groupFields         分组统计字段,可选
     */
    function group($modelClass,$filter = "",array $params=[],$sort="",$funcMap=[],$groupFields=[]);

    /**
     * 执行原生SQL语句
     * @param string $sql   SQL语句
     * @param array $params 输入参数
     */
    function sqlUpdate($sql,array $params=[]);

    /**
     * 原生SQL查询
     * @param string $sql
     * @param array $params
     * @param number $offset
     * @param number $limit
     */
    function sqlQuery($sql,array $params=[],$offset=0,$limit=-1);
}

