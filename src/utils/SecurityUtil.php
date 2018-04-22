<?php
namespace swiftphp\core\utils;

/**
 * 安全相关通用功能类
 * @author Tomix
 *
 */
class SecurityUtil
{
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
        $rndStr=strtolower(self::getRandomString(32));
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
}

