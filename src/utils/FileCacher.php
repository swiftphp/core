<?php
namespace swiftphp\core\utils;

use swiftphp\core\system\ICacher;
use swiftphp\core\config\IConfigurable;
use swiftphp\core\config\IConfiguration;
use swiftphp\core\io\Path;
use swiftphp\core\BuiltInConst;
use swiftphp\core\io\File;

/**
 * 文件缓存类
 * @author Tomix
 *
 */
class FileCacher implements ICacher,IConfigurable
{
    /**
     * 配置实例
     * @var IConfiguration
     */
    protected $m_config=null;


    /**
     * 注入配置实例
     * @param IConfiguration $value
     */
    public function setConfiguration(IConfiguration $value)
    {
        $this->m_config=$value;
    }

    /**
     * 读取缓存
     * {@inheritDoc}
     * @see \swiftphp\core\system\ICacher::get()
     */
    public function get($key)
    {
        $file=$this->getCacheDir().$key;
        if(file_exists($file)){
            $content=file_get_contents($file);
            $content=unserialize($content);
            return $content;
        }
        return null;
    }

    /**
     * 写入缓存
     * {@inheritDoc}
     * @see \swiftphp\core\system\ICacher::set()
     */
    public function set($key,$value)
    {
        $file=$this->getCacheDir().$key;
        file_put_contents($file, serialize($value));
    }

    /**
     * 移除缓存
     * {@inheritDoc}
     * @see \swiftphp\core\system\ICacher::remove()
     */
    public function remove($key)
    {
        $file=$this->getCacheDir().$key;
        if(file_exists($file))
            unlink($file);
    }

    /**
     * 清空缓存
     * {@inheritDoc}
     * @see \swiftphp\core\system\ICacher::clear()
     */
    public function clear()
    {
        $files=@scandir($this->getCacheDir());
        foreach ($files as $file){
            if($file != "." && $file != ".."){
                $file=$this->getCacheDir().$file;
                @unlink($file);
            }
        }
    }

    /**
     * 获取缓存时间
     * {@inheritDoc}
     * @see \swiftphp\core\system\ICacher::getCacheTime()
     */
    public function getCacheTime($key)
    {
        $file=$this->getCacheDir().$key;
        if(file_exists($file))
            return filemtime($file);
            return null;
    }

    /**
     * 缓存目录
     * @return string
     */
    private function getCacheDir()
    {
        $dir=__DIR__."/../../_cache/app/";
        if(!empty($this->m_config)){
            $dir=Path::combinePath($this->m_config->getBaseDir(), "_cache/");
            $_dir=$this->m_config->getConfigValue(BuiltInConst::$globalConfigSection, "cacheDir");
            if(empty($_dir)){
                $dir=Path::combinePath($dir,$_dir);
            }
            $dir=Path::combinePath($dir,"app/");
        }
        if(!file_exists($dir)){
            File::createDir($dir);
        }
        return $dir;
    }
}

