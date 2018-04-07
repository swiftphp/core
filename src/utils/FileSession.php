<?php
namespace swiftphp\core\utils;

use swiftphp\core\system\ISession;
use swiftphp\core\config\IConfigurable;
use swiftphp\core\system\ICacher;
use swiftphp\core\config\IConfiguration;

/**
 * 文件依赖Session
 * @author Tomix
 *
 */
class FileSession implements ISession,IConfigurable
{
    /**
     * 保存session id到cookie的键
     * @var string
     */
    protected $m_cookieKey="2206E9F3-8366-4247-AD3A-981102457C6D";

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
     * 配置实例
     * @var IConfiguration
     */
    protected $m_config=null;

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
     */
    protected function getCacher()
    {
        return $this->m_cacher;
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
     * 从session读取
     * {@inheritDoc}
     * @see \swiftphp\core\system\ISession::get()
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
     * {@inheritDoc}
     * @see \swiftphp\core\system\ISession::getAll()
     */
    public function getAll()
    {
        return $this->getCurrentSession();
    }

    /**
     * 写入session
     * {@inheritDoc}
     * @see \swiftphp\core\system\ISession::set()
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
            $sessionId=isset($_COOKIE[$this->m_cookieKey])?$_COOKIE[$this->m_cookieKey]:"";
            if(empty($sessionId)){
                $sessionId=AppUtil::newGuid();
                setcookie($this->m_cookieKey,$sessionId,0,"/",".".AppUtil::getDomain());
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
