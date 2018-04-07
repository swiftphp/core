<?php
namespace swiftphp\core\system;


/**
 * 时间间隔类
 * @author Tomix
 *
 */
class TimeSpan
{
    /**
     * 天数部分
     * @var int
     */
    private $m_days;

    /**
     * 小时数部分
     * @var int
     */
    private $m_hours;

    /**
     * 分钟数部分
     * @var int
     */
    private $m_minutes;

    /**
     * 秒数部分
     * @var int
     */
    private $m_seconds;

    /**
     * 构造函数
     * @param number $days    天数
     * @param number $hours   小时数
     * @param number $minutes 分钟数
     * @param number $seconds 秒数
     */
    public function __construct($days=0,$hours=0,$minutes=0,$seconds=0)
    {
        $this->m_days=$days;
        $this->m_hours=$hours;
        $this->m_minutes=$minutes;
        $this->m_seconds=$seconds;
    }

    /**
     * 天数部分
     * @return number
     */
    public function getDays()
    {
        return $this->m_days;
    }

    /**
     * 小时数部分
     * @return number
     */
    public function getHours()
    {
        return $this->m_hours;
    }

    /**
     * 分钟数部分
     * @return number
     */
    public function getMinutes()
    {
        return $this->m_minutes;
    }

    /**
     * 秒数部分
     * @return number
     */
    public function getSeconds()
    {
        return $this->m_seconds;
    }

    /**
     * 以小数表示的总天数
     * @return number
     */
    public function getTotalDays()
    {
        return $this->getDays()+$this->getHours()/24+$this->getMinutes()/24/60+$this->getSeconds()/24/3600;
    }

    /**
     * 以小数表示的总小时数
     * @return number
     */
    public function getTotalHours()
    {
        return $this->getDays()*24+$this->getHours()+$this->getMinutes()/60+$this->getSeconds()/3600;
    }

    /**
     * 以小数表示的总分钟数
     * @return number
     */
    public function getTotalMinutes()
    {
        return $this->getDays()*24*60+$this->getHours()*60+$this->getMinutes()+$this->getSeconds()/60;
    }

    /**
     * 总秒数
     * @return number
     */
    public function getTotalSeconds()
    {
        return $this->getDays()*24*3600+$this->getHours()*3600+$this->getMinutes()*60+$this->getSeconds();
    }
}

