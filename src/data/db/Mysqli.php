<?php
namespace swiftphp\core\data\db;

use swiftphp\core\data\db\IDatabase;

/**
 * 数据访问Mysqli实现(未实现)
 * @author Tomix
 *
 */
class Mysqli implements IDatabase
{
    /**
     * 连接
     */
    public function connect()
    {

    }

    /**
     * 关闭
     */
    public function close()
    {

    }

    /**
     * ping
     */
    public function ping()
    {

    }

    /**
     * 获取最后一次产生的异常
     * @return \Exception
     */
    public function getException()
    {

    }

    /**
     * 返回第一行第一列数据
     * @param string $sql
     * @return array
     */
    public function scalar($sql)
    {

    }

    /**
     * 返回一行数据集
     * @param string $sql
     */
    public function reader($sql)
    {

    }

    /**
     * 执行非查询
     * @param string $sql
     * @return void
     */
    public function execute($sql)
    {

    }


    /**
     * 执行返回记录集
     * @param string $sql
     * @param int  $offset
     * @param int  $limit
     * @return array
     */
    public function query($sql,$offset=0,$limit=-1)
    {

    }

    /**
     * 获取最后插入记录的ID
     */
    public function getInsertId()
    {

    }

    /**
     * 开始事务
     */
    public function begin()
    {

    }

    /**
     * 提交事务
     */
    public function commit()
    {

    }

    /**
     * 回滚事务
     */
    public function rollback()
    {

    }
}

