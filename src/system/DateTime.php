<?php
namespace swiftphp\core\system;

/**
 * 日期时间类
 * @author Tomix
 *
 */
class DateTime
{
    /**
     * 当前实例时间戳
     * @var int
     */
    private $_time=0;

    /**
     * 构造函数
     * @param string $time 实例时间戳
     */
    public function __construct($time="")
    {
        if($time==""){
            $time=time();
        }
        if(strlen($time)>10){
            $time=substr($time, 0,10);
        }
        $this->_time=$time;
    }

    /**
     * 转为时间戳
     * @return int
     */
    public function toTime()
    {
        return $this->_time;
    }

    /**
     * 转为系统\DateTime对象
     * @return \DateTime
     */
    public function toDateTime()
    {
        $dateTime=new \DateTime();
        $dateTime->setTimestamp($this->_time);
        return $dateTime;
    }

    /**
     * 格式化为年月日时分秒字符串
     * @param string $format 格式化
     */
    public function toString($format="Y-m-d H:i:s")
    {
        return date($format,$this->_time);
    }

    /**
     * 格式化为年月日字符串
     * @param string $format
     * @return string
     */
    public function toDateString($format="Y-m-d")
    {
        return $this->toString($format);
    }

    /**
     * 格式化为时分秒字符串
     * @param string $format
     */
    public function toTimeString($format="H:i:s")
    {
        return $this->toString($format);
    }

    /**
     * 获取当前实例表示的四位数年份
     */
    public function getYear()
    {
        return date("Y",$this->_time);
    }

    /**
     * 获取当前实例表示的二位数月份
     */
    public function getMonth()
    {
        return date("n",$this->_time);
    }

    /**
     * 获取当前实例表示的二位数日期
     */
    public function getDay()
    {
        return date("j",$this->_time);
    }

    /**
     * 获取当前实例表示的二位数小时数
     */
    public function getHour()
    {
        return date("G",$this->_time);
    }

    /**
     * 获取当前实例表示的二位数分钟数
     * @return number
     */
    public function getMinute()
    {
        $min=intval(date("i",$this->_time));
        return $min;
    }

    /**
     * 获取当前实例表示的二位数秒数
     */
    public function getSeconds()
    {
        $min=intval(date("s",$this->_time));
        return $min;
    }

    /**
     * 获取表示当前月份的最后一天的日期
     * @return string
     */
    public function getLastDayOfMonth()
    {
        return date("t",$this->_time);
    }

    /**
     * 以当前时间戳为基础增加秒数
     * @param integer $seconds
     * @return DateTime
     */
    public function addSeconds($seconds)
    {
        return new DateTime($this->_time+$seconds);
    }

    /**
     * 以当前时间戳为基础增加分钟数
     * @param integer $minutes
     * @return DateTime
     */
    public function addMinutes($minutes)
    {
        return new DateTime($this->_time+$minutes*60);
    }

    /**
     * 以当前时间戳为基础增加小时数
     * @param integer $hours
     * @return DateTime
     */
    public function addHours($hours)
    {
        return new DateTime($this->_time+$hours*3600);
    }

    /**
     * 以当前时间戳为基础增加天数
     * @param integer $days
     * @return DateTime
     */
    public function addDays($days)
    {
        $time=$this->_time+3600*24*$days;
        $obj=new DateTime($time);
        return $obj;
    }

    /**
     * 以当前时间戳为基础增加月份(自然月)
     * @param integer $months
     * @return DateTime
     */
    public function addMonths($months)
    {
        if($months==0){
            return new DateTime($this->_time);
        }
        $year=$this->getYear();
        $month=$this->getMonth();
        $day=$this->getDay();
        if($months>0){
            $addYears=floor($months/12);
            $addMonths=$months%12;
            while ($addMonths+$month>12){
                $addYears++;
                $addMonths-=12;
            }
            $str=strval($year+$addYears)."-".strval($addMonths+$month)."-01";
            $lastDay = date("t",strtotime($str));//当月的最后一天
            if($day>$lastDay){
                $day=$lastDay;
            }
            $str=strval($year+$addYears)."-".strval($addMonths+$month)."-".$day." ".date("H:i:s",$this->_time);
            return new DateTime(strtotime($str));
        }else{
            $months=abs($months);
            $addYears=floor($months/12);
            $addMonths=$months%12;
            while($month-$addMonths<=0){
                $addYears++;
                $addMonths-=12;
            }
            $str=strval($year-$addYears)."-".strval($month-$addMonths)."-01";
            $lastDay = date("t",strtotime($str));//当月的最后一天
            if($day>$lastDay){
                $day=$lastDay;
            }
            $str=strval($year-$addYears)."-".strval($month-$addMonths)."-".$day." ".date("H:i:s",$this->_time);
            return new DateTime(strtotime($str));
        }
    }

    /**
     * 以当前时间戳为基础增加年份数
     * @param int $years
     * @return DateTime
     */
    public function addYears($years)
    {
        return $this->addMonths($years*12);
    }

    /**
     * 计算时间差
     * @param DateTime $fromTime
     * @return TimeSpan
     */
    public function diffFrom(DateTime $time0)
    {
        $diff=$this->toTime()-$time0->toTime();
        $sign=$diff>=0?1:-1;
        $diff=abs($diff);
        $days=$sign*floor($diff/86400);
        $hours=$sign*floor($diff%86400/3600);

        $diff-=86400*abs($days)+3600*abs($hours);
        $minutes=$sign*floor($diff/60);
        $seconds=$sign*floor($diff%60);
        return new TimeSpan($days,$hours,$minutes,$seconds);
    }

    /**
     * 计算时间差
     * @param DateTime $time
     * @param DateTime $time0
     * @return TimeSpan
     */
    public static function diff(DateTime $time,DateTime $time0=null)
    {
        if($time0==null){
            $time0=new DateTime();
        }
        return $time->diffFrom($time0);
    }

    /**
     * 是否为该月的最后一天
     * @return bool
     */
    public function isLastDayOfMonth()
    {
        return (date("t",$this->_time)==$this->getDay());
    }

    /**
     * 从字符串转为DateTime对象
     * @param string $strTime
     * @return DateTime
     */
    public static function parse($strTime)
    {
        $time=new DateTime(strtotime($strTime));
        return $time;
    }

    /**
     * \DateTime转为DateTime
     * @param \DateTime $dateTime
     */
    public static function fromDateTime(\DateTime $dateTime)
    {
        return new DateTime($dateTime->getTimestamp());
    }

    /**
     * 表示当前时间的对象
     * @return DateTime
     */
    public static function now()
    {
        return new DateTime();
    }
}
