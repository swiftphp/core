<?php
namespace swiftphp\core\session\internal;

use swiftphp\core\session\ISession;
use swiftphp\core\cache\ICacher;
use swiftphp\core\common\util\SecurityUtil;
use swiftphp\core\common\util\AppUtil;

/**
 * 文件依赖Session
 * @author Tomix
 *
 */
class FileSession implements ISession
{
    /**
     * 保存session id到cookie的键
     * @var string
     */
    protected const COOKIE_KEY="2206E9F3-8366-4247-AD3A-981102457C6D";

    /**
     * 缓存管理器
     * @var ICacher
     */
    protected $m_cacher=null;

    /**
     * 当前session数据
     * @var array
     */
    protected $m_currentSession=[];

    /**
     * 当前session id
     * @var string
     */
    protected $m_currentSessionId="";

    /**
     * 设置缓存器对象
     * @param ICacher $value
     */
    public function setCacher(ICacher $value)
    {
        $this->m_cacher=$value;
    }

    /**
     * 获取缓存器对象
     * @return ICacher
     */
    protected function getCacher()
    {
        return $this->m_cacher;
    }

    /**
     * 从session读取
     * @return mixed|NULL
     */
    public function get($sessionKey)
    {
        $session=$this->getCurrentSession();
        if($session!=null && is_array($session) && array_key_exists($sessionKey, $session)){
            return $session[$sessionKey];
        }
        return null;
    }

    /**
     * 从session读取所有的数据
     * @return array
     */
    public function getAll()
    {
        return $this->getCurrentSession();
    }

    /**
     * 写入session
     * @return void
     */
    public function set($sessionKey,$value)
    {
        $session=$this->getCurrentSession();
        if(empty($session)){
            $session=[];
        }
        $session[$sessionKey]=$value;
        $this->setCurrentSession($session);
    }

    /**
     * 获取session id
     * {@inheritDoc}
     * @see \swiftphp\core\system\ISession::getSessionId()
     */
    public function getSessionId()
    {
        $sessionId=$this->m_currentSessionId;
        if(empty($sessionId)){
            $sessionId=isset($_COOKIE[self::COOKIE_KEY])?$_COOKIE[self::COOKIE_KEY]:"";
            if(empty($sessionId)){
                $sessionId=SecurityUtil::newGuid();
                setcookie(self::COOKIE_KEY,$sessionId,0,"/",".".AppUtil::getDomain());
            }
        }
        return $sessionId;
    }

    /**
     * 移除session
     * {@inheritDoc}
     * @see \swiftphp\core\system\ISession::remove()
     */
    public function remove($sessionKey)
    {
        $session=$this->getCurrentSession();
        if($session!=null && is_array($session) && array_key_exists($sessionKey, $session)){
            unset($session[$sessionKey]);
        }
        $this->setCurrentSession($session);
    }

    /**
     * 清空所有的session
     * {@inheritDoc}
     * @see \swiftphp\core\system\ISession::clear()
     */
    public function clear()
    {
        $sessionId=$this->getSessionId();
        $this->getCacher()->remove($sessionId);
    }

    /**
     * 动态设置session id,用于外部注入
     * {@inheritDoc}
     * @see \swiftphp\core\system\ISession::setSessionId()
     */
    public function setSessionId($value)
    {
        $this->m_currentSessionId=$value;
    }

    /**
     * 获取当前的所有session数据
     * @return array
     */
    protected function getCurrentSession()
    {
        if(empty($this->m_currentSession)){
            $sessionId=$this->getSessionId();
            $cacher=$this->getCacher();
            if(!$cacher){
                throw new \Exception("Fail to load cacher!");
            }
            $sess=$this->getCacher()->get($sessionId);
            if(!empty($sess)){
                $this->m_currentSession=(array)$sess;
            }
        }
        return (array)$this->m_currentSession;
    }

    /**
     * 设置到Session
     * @param array $value
     */
    protected function setCurrentSession(array $value)
    {
        $sessionId=$this->getSessionId();
        $this->getCacher()->set($sessionId, $value);
        $this->m_currentSession=null;
    }
}
