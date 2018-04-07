<?php
namespace swiftphp\core\utils;

/**
 * 通用功能类
 * @author Tomix
 *
 */
class AppUtil
{
    /**
     * 静态对象清单,键为类名,值为对象
     * @var array
     */
    private static $m_staticObjMap=[];

    /**
     * 取得根域名,不带端口号(如:domain.com)
     * @return string
     */
    public static function getDomain()
    {
        $domain=self::getAppDomain();
        $arr=explode(".", $domain);
        $c=count($arr);
        $root="";
        if($c>0)$root=$arr[$c-1];
        if($c>1)$root=$arr[$c-2].".".$root;
        if(strpos($root, ":")){
            $root=substr($root, 0,strpos($root, ":"));
        }
        return $root;
    }

    /**
     * 取得主机名(如:sub.domain.com:8080)
     * @return string
     */
    public static function getAppDomain()
    {
        return $_SERVER["HTTP_HOST"];
    }

    /**
     * 获取客户端IP
     * @return string|unknown
     */
    public static function getClientIp()
    {
        if (getenv("HTTP_CLIENT_IP")){
            $ip = getenv("HTTP_CLIENT_IP");
        }
        else if (getenv("HTTP_X_FORWARDED_FOR")){
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        }
        else if (getenv("HTTP_X_FORWARDED")){
            $ip = getenv("HTTP_X_FORWARDED");
        }
        else if (getenv("HTTP_FORWARDED_FOR")){
            $ip = getenv("HTTP_FORWARDED_FOR");
        }
        else if (getenv("HTTP_FORWARDED")){
            $ip = getenv("HTTP_FORWARDED");
        }
        else{
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }

    /**
     * 返回全球唯一标识字符串
     */
    public static function newGuid()
    {
        $address=strtolower($_SERVER["SERVER_NAME"]."/".$_SERVER["SERVER_ADDR"]);
        list($usec,$sec) = explode(" ",microtime());
        $timeMillis = $sec.substr($usec,2,3);
        $tmp = rand(0,1)?'-':'';
        $random = $tmp.rand(1000,  9999).rand(1000,  9999).rand(1000,  9999).rand(100,  999).rand(100,  999);
        $valueBeforeMD5 = $address.":".$timeMillis.":".$random;
        $value = md5($valueBeforeMD5);
        $raw = strtolower($value);
        return  substr($raw,0,8).'-'.substr($raw,8,4).'-'.substr($raw,12,4).'-'.substr($raw,16,4).'-'.substr($raw,20);
    }
    /**
     * 取得树型数据的上级数据(包括自身)
     * @param $source 		数据源
     * @param $idField  	主键字段名
     * @param $pidField		上级数据主键字段名
     * @param $fieldValue	主键字段值
     * @param $includeSelf	是否包括自身
     * @return array
     */
    public static function getAncestors($source,$idField,$pidField,$fieldValue,$includeSelf=true)
    {
        $returnValue=array();
        $current=array();
        foreach($source as $row){
            if($row[$idField]==$fieldValue){
                $current=$row;
                if($includeSelf)
                    $returnValue[]=$current;
                    break;
            }
        }
        while($current[$pidField] > 0){
            $current=self::_getAncestors($source,$idField,$current[$pidField]);
            $returnValue[]=$current;
        }

        //echo $returnValue[1][$idField];
        return array_reverse($returnValue);
    }

    /**
     *  取得树型数据的子级数据(包括自身)
     * @param $source 		数据源
     * @param $idField  	主键字段名
     * @param $pidField		上级数据主键字段名
     * @param $fieldValue	主键字段值
     * @param $level  		树型数据深度,负数表示无限制.默认为无限制
     * @return array
     */
    public static function getOffSprings($source,$idField,$pidField,$fieldValue,$includeSelf=true,$level=-1)
    {
        $returnValue=self::_getOffSprings($source,$idField,$pidField,$fieldValue,$level);

        //添加自身
        if($includeSelf){
            foreach($source as $arr){
                if($arr[$idField] == $fieldValue){
                    $returnValue[]=$arr;
                }
            }
        }

        return $returnValue;
    }

    /**
     * 树状下拉框数据
     * @param unknown $source
     * @param unknown $idField
     * @param unknown $pidField
     * @param unknown $titleField
     * @param string $separator
     * @param string $prefix
     * @param unknown $level
     * @return unknown[]
     */
    public static function getSelectTree($source,$idField,$pidField,$titleField,$separator="&nbsp;&nbsp;&nbsp;&nbsp;",$prefix="|-",$level=-1)
    {
        $target=[];
        foreach ($source as $item){
            $pid=$item[$pidField];
            if(empty($pid)){
                $id=$item[$idField];
                $title=$item[$titleField];
                $target[$id]=$title;
                self::_getSelectTreeChildren($target,$id,$source,$idField,$pidField,$titleField,$separator,$prefix,$level,1);
            }
        }
        return $target;
    }

    /**
     * 计算数组差集,专用于用数字索引的二维数组比较
     * @param $source
     * @param $subSource
     * @param $idField
     * @return array
     */
    public static function arrayDiff($source,$subSource,$idField)
    {
        $keys=array();
        foreach($subSource as $item){
            $keys[]=$item[$idField];
        }

        $returnValue=[];
        foreach($source as $item){
            if(!in_array($item[$idField],$keys)){
                $returnValue[]=$item;
            }
        }
        return $returnValue;
    }

    /**
     * 可逆加密字符串
     * @param $input 输入字串
     * @param $password 密匙
     * @return string 加密后的字串
     */
    public static function encrypt($input,$password="51defe64-b73d-4c59-bee8-a5808dd97be2")
    {
        $lockstream = 'st=lDEFABCNOPyzghi_jQRST-UwxkVWXYZabcdef+IJK6/7nopqr89LMmGH012345uv';

        //随机找一个数字，并从密锁串中找到一个密锁值
        $lockLen = strlen($lockstream);
        $lockCount = rand(0,$lockLen-1);
        $randomLock = $lockstream[$lockCount];
        //结合随机密锁值生成MD5后的密码
        $password = md5($password.$randomLock);
        //开始对字符串加密
        $input = base64_encode($input);
        $tmpStream = '';
        $i=0;$j=0;$k = 0;
        for ($i=0; $i<strlen($input); $i++) {
            $k = $k == strlen($password) ? 0 : $k;
            $j = (strpos($lockstream,$input[$i])+$lockCount+ord($password[$k]))%($lockLen);
            $tmpStream .= $lockstream[$j];
            $k++;
        }
        return $tmpStream.$randomLock;
    }

    /**
     * 解密经过可逆加密过的字符串
     * @param $input 输入字串
     * @param $password 密匙
     * @return string 解密后的字串
     */
    public static function decrypt($input,$password="51defe64-b73d-4c59-bee8-a5808dd97be2")
    {
        $lockstream = 'st=lDEFABCNOPyzghi_jQRST-UwxkVWXYZabcdef+IJK6/7nopqr89LMmGH012345uv';
        $lockLen = strlen($lockstream);
        //获得字符串长度
        $txtLen = strlen($input);
        //截取随机密锁值
        $randomLock = $input[$txtLen - 1];
        //获得随机密码值的位置
        $lockCount = strpos($lockstream,$randomLock);
        //结合随机密锁值生成MD5后的密码
        $password = md5($password.$randomLock);
        //开始对字符串解密
        $input = substr($input,0,$txtLen-1);
        $tmpStream = '';
        $i=0;$j=0;$k = 0;
        for ($i=0; $i<strlen($input); $i++) {
            $k = $k == strlen($password) ? 0 : $k;
            $j = strpos($lockstream,$input[$i]) - $lockCount - ord($password[$k]);
            while($j < 0){
                $j = $j + ($lockLen);
            }
            $tmpStream .= $lockstream[$j];
            $k++;
        }
        return base64_decode($tmpStream);
    }

    /**
     * 产生64位不可逆加密字串(密码)
     * @param $input 输入字串
     * @return string 64位不可逆加密字串(密码)
     */
    public static function encryptPassword($input)
    {
        $rndStr=strtolower(AppUtil::getRandomString(32));
        $mixedStr=$rndStr.$input;
        return $rndStr.md5($mixedStr);
    }

    /*
     * 对比字串与加密后的字串是否一致
     */
    public static function checkPassword($checkString,$encryptedString)
    {
        if(strlen($encryptedString) != 64){
            return false;
        }
        $preString=substr($encryptedString,0,32);
        $sufString=substr($encryptedString,32);
        if(md5($preString.$checkString) == $sufString){
            return true;
        }
        return false;
    }

    //產生隨機字符串
    //參數說明:$length,返回字符串長度;$mode返回模式(0:数字与字母组合;1:纯数字;2纯字母)
    /**
     * 产品随机字串
     * @param $length 需要产品的字串长度
     * @param $mode  返回模式:0:数字与字母组合;1:纯数字;2纯字母;默认为0.
     * @return string
     */
    public static function getRandomString($length,$mode=0)
    {
        $chars=["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9"];
        if($mode==1){
            $chars=["0","1","2","3","4","5","6","7","8","9"];
        }
        if($mode==2){
            $chars=["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z"];
        }
        shuffle($chars);
        $max=count($chars)-1;
        $returnValue = "";
        while(strlen($returnValue)<$length){
            $randIndex=rand(0,$max);
            $returnValue .= $chars[$randIndex];
        }
        return $returnValue;
    }

    /**
     * 私有方法:取得树型数据的子级数据,该方法是getOffSprings()的辅助方法
     * @param $source 		数据源
     * @param $idField  	主键字段名
     * @param $pidField		上级数据主键字段名
     * @param $fieldValue	主键字段值
     * @return array
     */
    private static function _getOffSprings($source,$idField,$pidField,$fieldValue,$level,$currentLevel=0)
    {
        if($level<0){
            $level=9999;
        }
        if($currentLevel>=$level){
            return [];
        }
        $returnValue=[];
        foreach($source as $arr){
            if($arr[$pidField] == $fieldValue){
                $returnValue[]=$arr;
                $index=array_search($arr,$source);
                if(null!=$index){
                    unset($source[$index]);
                }
            }
        }
        $rootArray=$returnValue;
        foreach($rootArray as $arr){
            $temp=self::_getOffSprings($source,$idField,$pidField,$arr[$idField],$level,$currentLevel+1);
            if(count($temp)>0){
                $returnValue=array_merge_recursive($returnValue,$temp);
            }
        }
        return $returnValue;
    }

    /**
     * 私有方法:取得树型数据的上级数据.该方法是getAncestors()的辅助方法
     * @param $source 		数据源
     * @param $idField  	主键字段名
     * @param $fieldValue	主键字段值
     * @return array
     */
    private static function _getAncestors($source,$idField,$fieldValue)
    {
        foreach($source as $row){
            if($row[$idField] == $fieldValue){
                return $row;
            }
        }
    }

    /**
     * 树状下拉框数据
     * @param unknown $target
     * @param unknown $pId
     * @param unknown $source
     * @param unknown $idField
     * @param unknown $pidField
     * @param unknown $titleField
     * @param unknown $separator
     * @param unknown $prefix
     * @param unknown $level
     * @param unknown $currentLevel
     */
    private static function _getSelectTreeChildren(&$target,$pId,$source,$idField,$pidField,$titleField,$separator,$prefix,$level,$currentLevel)
    {
        if($level<0){
            $level=9999;
        }
        if($currentLevel>$level){
            return;
        }
        $sep="";
        for($i=0;$i<$currentLevel;$i++){
            $sep.=$separator;
        }
        foreach ($source as $item){
            $_pid=$item[$pidField];
            if($_pid==$pId){
                $id=$item[$idField];
                $title=$sep.$prefix.$item[$titleField];
                $target[$id]=$title;
                self::_getSelectTreeChildren($target,$id,$source,$idField,$pidField,$titleField,$separator,$prefix,$level,$currentLevel+1);

            }
        }
    }
}

