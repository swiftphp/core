<?php
namespace swiftphp\core\cache\internal;

use swiftphp\core\io\File;
use swiftphp\core\cache\ICacher;

/**
 * 文件缓存类
 * @author Tomix
 *
 */
class FileCacher implements ICacher
{
    /**
     * 缓存目录,相对于应用根目录
     * @var string
     */
    protected $m_cacheDir;

    /**
     * 设置缓存目录,相对于应用根目录
     * @param string $value
     */
    public function setCacheDir($value)
    {
        $this->m_cacheDir=$value;
    }

    /**
     * 缓存目录
     * @return string
     */
    public function getCacheDir()
    {
        $dir=$this->m_cacheDir;
        if(empty($dir)){
            $dir=__DIR__."/../../../../../../_cache";
        }
        if(!file_exists($dir)){
            File::createDir($dir);
        }
        return rtrim($dir)."/";
    }

    /**
     * 读取缓存
     * {@inheritDoc}
     * @see \swiftphp\core\cache\ICacher::get()
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
     * @see \swiftphp\core\cache\ICacher::set()
     */
    public function set($key,$value)
    {
        $file=$this->getCacheDir().$key;
        file_put_contents($file, serialize($value));
    }

    /**
     * 移除缓存
     * {@inheritDoc}
     * @see \swiftphp\core\cache\ICacher::remove()
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
     * @see \swiftphp\core\cache\ICacher::clear()
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
     * @see \swiftphp\core\cache\ICacher::getCacheTime()
     */
    public function getCacheTime($key)
    {
        $file=$this->getCacheDir().$key;
        if(file_exists($file))
            return filemtime($file);
            return null;
    }
}

