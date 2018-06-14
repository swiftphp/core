<?php
namespace swiftphp\core\data\db;

use swiftphp\core\system\ILogger;
use swiftphp\core\system\Console;

/**
 * 数据访问PDO实现
 *
 * @author Tomix
 *
 */
class MysqlPDO implements IDatabase
{
    /**
     * dsn
     * @var string
     */
    private $m_dsn;

    /**
     * 登录账号
     * @var string
     */
    private $m_user;

    /**
     * 登录密码
     * @var string
     */
    private $m_password;

    /**
     * 最后一条异常
     * @var \Exception
     */
    private $m_exception;

    /**
     * pdo实例
     * @var \PDO
     */
    private $m_pdo=null;

    /**
     * 日志记录
     * @var ILogger
     */
    private $m_logger=null;

    /**
     * 是否调试状态
     * @var bool
     */
    private $m_debug=false;

    /**
     * 设置DSN
     * @param string $value
     */
    public function setDsn($value)
    {
        $this->m_dsn=$value;
    }

    /**
     * 登录账号
     * @param string $value
     */
    public function setUser($value)
    {
        $this->m_user=$value;
    }

    /**
     * 登录密码
     * @param string $value
     */
    public function setPassword($value)
    {
        $this->m_password=$value;
    }

    /**
     * 日志记录器
     * @param ILogger $value
     */
    public function setLogger(ILogger $value)
    {
        $this->m_logger=$value;
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
     * 获取最后一次产生的异常
     * @return \Exception
     */
    public function getException()
    {
        return $this->m_exception;
    }

    /**
     * 连接
     */
    public function connect()
    {
        try{
            $this->m_pdo=new \PDO($this->m_dsn, $this->m_user, $this->m_password);
            $this->m_pdo->setAttribute(\PDO::ATTR_ERRMODE,  \PDO::ERRMODE_EXCEPTION);
        }catch (\Exception $ex){
            $this->fatchException($ex);
            throw $ex;
        }
    }

    /**
     * 关闭
     */
    public function close()
    {
        $this->m_pdo=null;
    }

    /**
     * ping
     */
    public function ping()
    {
        return $this->m_pdo!=null;
    }

    /**
     * 返回第一行第一列数据
     * @param string $sql SQL语句
     * @param array $params 输入参数
     */
    public function scalar($sql,array $params=[])
    {
        try{
            $query=$this->pdoExecute($sql,$params);
            if($query){
                $rs=$query->fetch();
                if($rs && count($rs)>0){
                    return $rs[0];
                }
            }
        }catch (\Exception $ex){
            $this->fatchException($ex);
            throw $ex;
        }
        return null;
    }

    /**
     * 返回一行数据集
     * @param string $sql SQL语句
     * @param array $params 输入参数
     */
    public function reader($sql,array $params=[])
    {
        try{
            $query=$this->pdoExecute($sql,$params);
            if($query){
                $query->setFetchMode(\PDO::FETCH_ASSOC);
                $rs=$query->fetch();
                if($rs){
                    return $rs;
                }
            }
        }catch (\Exception $ex){
            $this->fatchException($ex);
            throw $ex;
        }
        return null;
    }

    /**
     * 执行非查询
     * @param string $sql SQL语句
     * @param array $params 输入参数
     * @return int 映射记录数,-1表示执行失败
     */
    public function execute($sql,array $params=[])
    {
        $this->autoConnect();
        try{
            $query=$this->pdoExecute($sql,$params);
            if($query){
                return $query->rowCount();
            }
        }catch (\Exception $ex){
            $this->fatchException($ex);
            throw $ex;
        }
        return -1;
    }

    /**
     * 执行返回记录集
     * @param string $sql SQL语句(不包含分页表达式)
     * @param array $params 输入参数
     * @param int $start
     * @param int $length
     * @return array
     */
    public function query($sql,array $params=[], $offset = 0, $limit = -1)
    {
        if($offset>= 0 && $limit>= 0){
            $sql .= " LIMIT ".$offset.",".$limit;
        }
        try{
            $query=$this->pdoExecute($sql,$params);
            if($query){
                $query->setFetchMode(\PDO::FETCH_ASSOC);
                $rs=$query->fetchAll();
                return $rs;
            }
        }catch (\Exception $ex){
            $this->fatchException($ex);
            throw $ex;
        }
    }

    /**
     * 获取最后插入记录的ID
     */
    public function getInsertId()
    {
        $this->autoConnect();
        try{
            return $this->m_pdo->lastInsertId();
        }catch (\Exception $ex){
            $this->fatchException($ex);
            throw $ex;
        }
    }

    /**
     * 开始事务
     */
    public function begin()
    {
        $this->autoConnect();
        $this->m_pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
        $this->m_pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->m_pdo->commit();
        $this->m_pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $this->m_pdo->rollBack();
        $this->m_pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
    }

    /**
     * PDO执行
     * @return \PDOStatement
     */
    private function pdoExecute($sql,array $params=[])
    {
        if($this->m_debug){
            Console::printLine($sql."\r\n");
        }
        $this->autoConnect();
        try{
            $statement = $this->m_pdo->prepare($sql);
            if($statement->execute($params)){
                return $statement;
            }
        }catch (\Exception $ex){
            $this->fatchException($ex);
            throw $ex;
        }
    }

    /**
     * 自动连接数据库
     */
    private function autoConnect()
    {
        if(!$this->ping()){
            $this->connect();
        }
    }

    /**
     * 捕捉异常
     * @param \Exception $ex
     */
    private function fatchException(\Exception $ex)
    {
        $this->m_exception=$ex;
        if(!is_null($this->m_logger)){
            $this->m_logger->logException($ex,"pdo ex");
            //$this->m_logger->log($ex->getCode().":".$ex->getMessage()."\r\n".$ex->getTraceAsString(),"pdo ex","err");
        }
    }
}

